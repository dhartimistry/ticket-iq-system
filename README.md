# Ticket IQ System

An AI-powered ticket classification system that automatically categorizes support tickets using OpenAI's GPT models. Built with Laravel, this system provides intelligent ticket categorization with fallback mechanisms and rate limiting.

## Requirements

- PHP 8.1 or higher
- MySQL 5.7 or higher
- Composer
- Node.js & NPM
- OpenAI API key

## Quick Setup

1. **Clone and Install Dependencies**
   ```bash
   git clone https://github.com/dhartimistry/ticket-iq-system.git
   cd ticket-iq-system
   composer install
   npm install
   ```

2. **Environment Setup**
   ```bash
   # Copy environment file
   cp .env.example .env
   
   # Configure MySQL in .env file
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ticket_iq
   DB_USERNAME=your_mysql_username
   DB_PASSWORD=your_mysql_password
   
   # Generate application key
   php artisan key:generate
   ```

3. **Database Setup**
   ```bash
   # Create MySQL database
   mysql -u root -p
   mysql> CREATE DATABASE ticket_iq;
   mysql> exit
   
   # Run migrations and seeders
   php artisan migrate --seed
   ```

4. **Build Assets & Start Server**
   ```bash
   npm run build
   php artisan serve
   ```

5. **Start Queue Worker**
   ```bash
   php artisan queue:work
   ```

## Features

- AI-powered ticket classification using OpenAI GPT
- Keyword-based fallback classification
- Rate-limited API calls
- Background job processing
- RESTful API endpoints
- Bulk classification support

## What I'd Do With More Time

1. **Enhanced Classification**
   - Train custom ML model for faster classification
   - Implement learning from user corrections
   - Add more classification categories

2. **Performance Improvements**
   - Add caching layer for frequently accessed tickets
   - Implement batch processing for bulk operations
   - Optimize database queries and indexes

3. **Additional Features**
   - Real-time notifications for classification results
   - User feedback system for improving accuracy
   - Advanced analytics dashboard
   - Integration with popular ticketing systems

## API Usage

```bash
# Create a new ticket
POST /api/tickets
{
    "subject": "Payment Issue",
    "body": "Cannot process payment"
}

# List tickets
GET /api/tickets
GET /api/tickets?status=open
GET /api/tickets?category=billing

# Update ticket
PATCH /api/tickets/{id}
{
    "status": "closed",
    "category": "billing"
}

# Manual classification
POST /api/tickets/{id}/classify
```

## Bulk Classification
```bash
php artisan tickets:classify-bulk
```

## Testing
```bash
php artisan test
```

## License

This project is licensed under the MIT License.
