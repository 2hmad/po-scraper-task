# Palm Outsourcing Product Scraper (Task)

A web scraping utility designed to extract and process data from Amazon.

## Prerequisites

- Docker
- Docker Compose
- Internet Connection

## Installation

1. Clone the repository
2. Run `docker compose up --build -d` to build the container

## Usage

1. Navigate to `http://localhost:3000/products` in your browser
2. You will see a list of products from Amazon (Scraped while the container was running)

## Database

The application uses a MySQL database to store the scraped data. The database is created and seeded with scraped data from amazon when the container is built.

### MySQL Configuration

- Host: `localhost`
- Port: `3306`
- Username: `root`
- Password: `secret`
- Database Name: `po_scraper`
- Table Name: `products`

### Table Schema

- Table Schema:
  - `id` (INT, Primary Key, Auto Increment)
  - `title` (VARCHAR)
  - `price` (VARCHAR)
  - `image_url` (VARCHAR)
  - `created_at` (TIMESTAMP)
  - `updated_at` (TIMESTAMP)
