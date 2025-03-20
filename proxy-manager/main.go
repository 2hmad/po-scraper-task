package main

import (
	"context"
	"encoding/json"
	"io"
	"log"
	"math/rand"
	"net/http"
	"net/url"
	"strings"
	"sync"
	"time"
)

type ProxyManager struct {
	proxies []string
	mutex   sync.RWMutex
}

type ProxyResponse struct {
	Proxy string `json:"proxy"`
}

func NewProxyManager() *ProxyManager {
	pm := &ProxyManager{
		proxies: []string{},
	}

	go pm.startProxyFetcher()

	return pm
}

func (pm *ProxyManager) startProxyFetcher() {
	pm.fetchProxies()

	for range time.Tick(5 * time.Minute) {
		pm.fetchProxies()
	}
}

func (pm *ProxyManager) fetchProxies() {
	resp, err := http.Get("https://www.proxy-list.download/api/v1/get?type=http")
	if err != nil {
		log.Printf("Error fetching proxies: %v", err)
		return
	}
	defer resp.Body.Close()

	bodyBytes, err := io.ReadAll(resp.Body)
	if err != nil {
		log.Printf("Error reading response body: %v", err)
		return
	}

	proxyList := strings.Split(string(bodyBytes), "\n")
	var filteredProxies []string
	for _, proxy := range proxyList {
		proxy = strings.TrimSpace(proxy)
		if proxy != "" {
			filteredProxies = append(filteredProxies, proxy)
		}
	}

	pm.UpdateProxyList(filteredProxies)
	log.Printf("Fetched %d proxies", len(filteredProxies))
}

func (pm *ProxyManager) GetRandomProxy() string {
    pm.mutex.RLock()
    defer pm.mutex.RUnlock()

    if len(pm.proxies) == 0 {
        return ""
    }

    proxy := pm.proxies[rand.Intn(len(pm.proxies))]
    return strings.TrimSpace(proxy)
}

func (pm *ProxyManager) testProxy(proxyStr string) bool {
    proxyURL, err := url.Parse("http://" + proxyStr)
    if err != nil {
        return false
    }

    transport := &http.Transport{
        Proxy: http.ProxyURL(proxyURL),
    }

    client := &http.Client{
        Transport: transport,
        Timeout:   5 * time.Second,
    }

    ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
    defer cancel()

    req, err := http.NewRequestWithContext(ctx, "GET", "https://www.google.com", nil)
    if err != nil {
        return false
    }

    resp, err := client.Do(req)
    if err != nil {
        return false
    }
    defer resp.Body.Close()

    return resp.StatusCode == 200
}

func (pm *ProxyManager) UpdateProxyList(candidateProxies []string) {
    pm.mutex.Lock()
    defer pm.mutex.Unlock()

    var workingProxies []string

    results := make(chan string)
    sem := make(chan bool, 10)

    for _, proxy := range candidateProxies {
        proxy := strings.TrimSpace(proxy)
        if proxy == "" {
            continue
        }

        go func(p string) {
            sem <- true
            defer func() { <-sem }()

            if pm.testProxy(p) {
                results <- p
            } else {
                results <- ""
            }
        }(proxy)
    }

    for i := 0; i < len(candidateProxies); i++ {
        if proxy := <-results; proxy != "" {
            workingProxies = append(workingProxies, proxy)
        }
    }

    log.Printf("Found %d working proxies out of %d candidates", len(workingProxies), len(candidateProxies))
    pm.proxies = workingProxies
}

func main() {
	proxyManager := NewProxyManager()

	http.HandleFunc("/proxy", func(w http.ResponseWriter, r *http.Request) {
		proxy := proxyManager.GetRandomProxy()
		response := ProxyResponse{Proxy: proxy}

		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(response)
	})

    http.HandleFunc("/health", func(w http.ResponseWriter, r *http.Request) {
        w.WriteHeader(http.StatusOK)
    })

	log.Println("Proxy manager service running on :8081")
	log.Fatal(http.ListenAndServe(":8081", nil))
}
