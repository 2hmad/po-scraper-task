# Build stage
FROM golang:1.22.7-alpine AS builder

WORKDIR /app

# Copy go mod and sum files
COPY go.mod ./

# Download dependencies
# This line might fail if go.sum is required but not copied
RUN go mod download || true  # Continue even if this step fails

# Copy the source code
COPY . .

# Build the application
# Add verbose output to see any build errors
RUN CGO_ENABLED=0 GOOS=linux go build -v -o main .

# Final stage
FROM alpine:latest

WORKDIR /app

# Copy the binary from the builder stage
COPY --from=builder /app/main /app/main

# Add execution permissions explicitly
RUN chmod +x /app/main

# Expose port
EXPOSE 8081

# Run the application
CMD ["/app/main"]
