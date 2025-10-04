# News Aggregator API

A powerful Laravel-based news aggregation system that fetches, normalizes, and serves articles from multiple news sources through a RESTful API. Built with SOLID principles, DRY architecture, and comprehensive filtering capabilities.

## 📋 Table of Contents

- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [API Documentation](#api-documentation)
- [News Sources](#news-sources)
- [Scheduled Tasks](#scheduled-tasks)
- [Testing](#testing)
- [Architecture](#architecture)
- [Troubleshooting](#troubleshooting)
- [License](#license)

## ✨ Features

### Core Features
- **Multi-Source Aggregation**: Fetches news from NewsAPI, The Guardian, and BBC News
- **Automated Updates**: Hourly scheduled fetching of latest articles
- **RESTful API**: Comprehensive API endpoints with filtering, searching, and pagination
- **User Preferences**: Personalized news feeds based on user-selected sources, categories, and authors
- **Data Normalization**: Consistent article structure across different sources
- **Search & Filter**: Full-text search with multiple filtering options
- **API Authentication**: Secure endpoints using Laravel Sanctum

### Technical Features
- **SOLID Principles**: Clean, maintainable architecture
- **Factory Pattern**: Extensible news source integration
- **Error Handling**: Robust logging and error management
- **Database Optimization**: Indexed queries for fast retrieval
- **Comprehensive Testing**: PHPUnit tests for all endpoints
- **API Resources**: Consistent JSON responses

## 🔧 System Requirements

- PHP 8.1 or higher
- Composer
- MySQL 8.0+ or PostgreSQL 13+
- Laravel 10.x
- API Keys for:
  - [NewsAPI](https://newsapi.org/)
  - [The Guardian](https://open-platform.theguardian.com/)

## 📦 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/mobiblunt/news-agg.git
cd news-agg
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Environment Variables

Edit `.env` file:

```env
APP_NAME="News Aggregator"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=news_aggregator
DB_USERNAME=your_username
DB_PASSWORD=your_password

# API Keys
NEWSAPI_KEY=your_newsapi_key
GUARDIAN_API_KEY=your_guardian_api_key
```

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Fetch Initial Data

```bash
php artisan fetch:news
```

### 7. Start Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api`

## ⚙️ Configuration

### API Keys Setup

#### NewsAPI
1. Visit [NewsAPI.org](https://newsapi.org/)
2. Sign up for a free account
3. Get your API key
4. Add to `.env`: `NEWSAPI_KEY=your_key_here`

#### The Guardian
1. Visit [The Guardian Open Platform](https://open-platform.theguardian.com/)
2. Register for API access
3. Get your API key
4. Add to `.env`: `GUARDIAN_API_KEY=your_key_here`

### Services Configuration

Add to `config/services.php`:

```php
'newsapi' => [
    'key' => env('NEWSAPI_KEY'),
],
'guardian' => [
    'key' => env('GUARDIAN_API_KEY'),
],
```

## 🗄️ Database Setup

### Database Schema

#### Articles Table
```sql
- id: bigint (primary key)
- title: string
- content: text
- author: string
- source: string (indexed)
- source_id: string (unique)
- category: string (indexed)
- published_at: datetime (indexed)
- url: string
- image_url: string
- created_at: timestamp
- updated_at: timestamp
```

#### User Preferences Table
```sql
- id: bigint (primary key)
- user_id: bigint (foreign key, indexed)
- sources: json
- categories: json
- authors: json
- created_at: timestamp
- updated_at: timestamp
```

### Migrations

```bash
# Run all migrations
php artisan migrate

# Fresh migration (caution: deletes all data)
php artisan migrate:fresh

# Rollback last migration
php artisan migrate:rollback
```

## 📚 API Documentation

### Base URL
```
http://localhost:8000/api
```

### Public Endpoints

#### 1. Get All Articles

```http
GET /api/articles
```

**Query Parameters:**
- `search` - Search in title and content
- `source` - Filter by source (NewsAPI, The Guardian, BBC News)
- `category` - Filter by category
- `author` - Filter by author
- `date` - Filter articles from date (YYYY-MM-DD)
- `sources` - Multiple sources (comma-separated)
- `categories` - Multiple categories (comma-separated)
- `sort_by` - Sort field (published_at, title, source)
- `sort_order` - Sort order (asc, desc)
- `per_page` - Items per page (max: 100)
- `page` - Page number

**Example:**
```bash
curl -X GET "http://localhost:8000/api/articles?search=technology&source=NewsAPI&per_page=20"
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Article Title",
      "content": "Article content...",
      "author": "John Doe",
      "source": "NewsAPI",
      "category": "technology",
      "published_at": "2025-10-03T10:00:00.000000Z",
      "url": "https://example.com/article",
      "image_url": "https://example.com/image.jpg"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/articles?page=1",
    "last": "http://localhost:8000/api/articles?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/articles?page=2"
  },
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100
  }
}
```

#### 2. Get Single Article

```http
GET /api/articles/{id}
```

**Example:**
```bash
curl -X GET "http://localhost:8000/api/articles/1"
```

#### 3. Get Filter Options

```http
GET /api/articles/filters
```

**Response:**
```json
{
  "data": {
    "sources": ["NewsAPI", "The Guardian", "BBC News"],
    "categories": ["technology", "business", "sports"],
    "authors": ["Author 1", "Author 2"]
  }
}
```

### Protected Endpoints (Require Authentication)

#### Authentication Setup

1. **Create User:**
```bash
php artisan tinker
```
```php
$user = \App\Models\User::create([
    'name' => 'Your Name',
    'email' => 'your@email.com',
    'password' => bcrypt('password')
]);
```

2. **Generate Token:**
```php
$token = $user->createToken('api-token')->plainTextToken;
echo $token;
```

3. **Use Token in Requests:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

#### 4. Get User Preferences

```http
GET /api/preferences
```

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Response:**
```json
{
  "data": {
    "preferences": {
      "sources": ["NewsAPI"],
      "categories": ["technology", "business"],
      "authors": ["John Doe"]
    },
    "available_options": {
      "sources": ["NewsAPI", "The Guardian", "BBC News"],
      "categories": ["technology", "business", "sports"],
      "authors": ["Author 1", "Author 2"]
    }
  }
}
```

#### 5. Create/Update Preferences

```http
POST /api/preferences
PUT /api/preferences
```

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

**Body:**
```json
{
  "sources": ["NewsAPI", "The Guardian"],
  "categories": ["technology", "business"],
  "authors": ["John Doe"]
}
```

**Response:**
```json
{
  "message": "Preferences saved successfully",
  "data": {
    "sources": ["NewsAPI", "The Guardian"],
    "categories": ["technology", "business"],
    "authors": ["John Doe"]
  }
}
```

#### 6. Delete Preferences

```http
DELETE /api/preferences
```

**Response:**
```json
{
  "message": "Preferences deleted successfully"
}
```

#### 7. Get Personalized Feed

```http
GET /api/articles/personalized/feed
```

**Query Parameters:** (same as Get All Articles)

Returns articles matching user's preferences.

## 🔄 News Sources

### Supported Sources

#### 1. NewsAPI
- **Type**: General news aggregator
- **Coverage**: Global news from 80,000+ sources
- **Rate Limit**: 100 requests/day (free tier)
- **Categories**: Technology, Business, Entertainment, Health, Science, Sports

#### 2. The Guardian
- **Type**: British newspaper
- **Coverage**: UK and international news
- **Rate Limit**: 5,000 requests/day (free tier)
- **Categories**: News, Opinion, Sport, Culture, Lifestyle

#### 3. BBC News
- **Type**: British public broadcaster
- **Coverage**: Global news (via NewsAPI)
- **Note**: Accessed through NewsAPI

### Adding New Sources

1. Create a new source class:
```bash
touch app/Services/NewsAggregator/NewSourceName.php
```

2. Extend `AbstractNewsSource`:
```php
<?php

namespace App\Services\NewsAggregator;

class NewSourceName extends AbstractNewsSource
{
    public function __construct()
    {
        $this->baseUrl = 'https://api.newsource.com';
        $this->apiKey = config('services.newsource.key');
        $this->sourceName = 'New Source';
    }

    protected function buildUrl(array $params): string
    {
        // Build API URL
    }

    protected function extractArticles(array $response): array
    {
        // Extract articles from response
    }

    public function normalizeArticle(array $article): array
    {
        // Normalize to standard format
    }
}
```

3. Register in `NewsAggregatorService`:
```php
public function __construct()
{
    $this->sources = [
        new NewsApiSource(),
        new GuardianSource(),
        new BbcSource(),
        new NewSourceName(), // Add here
    ];
}
```

## ⏰ Scheduled Tasks

### Automatic News Fetching

The application automatically fetches news every hour using Laravel's task scheduler.

#### Setup Cron Job

Add to your crontab:
```bash
crontab -e
```

Add this line:
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

#### Scheduled Tasks

1. **Hourly News Fetch**
   - Runs: Every hour
   - Command: `php artisan fetch:news`
   - Purpose: Fetch latest articles from all sources

2. **Daily Cleanup**
   - Runs: Daily at 2:00 AM
   - Purpose: Delete articles older than 30 days

3. **Daily Statistics**
   - Runs: Daily at 11:00 PM
   - Purpose: Log article statistics

#### Manual Commands

```bash
# Fetch news manually
php artisan fetch:news

# View scheduled tasks
php artisan schedule:list

# Run scheduler once (for testing)
php artisan schedule:run
```

## 🧪 Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suite

```bash
# Article API tests
php artisan test tests/Feature/Api/ArticleApiTest.php

# Preferences API tests
php artisan test tests/Feature/Api/PreferencesApiTest.php
```

### Run Specific Test

```bash
php artisan test --filter test_can_get_all_articles
```

### Test with Coverage

```bash
php artisan test --coverage
```

### Test Database Setup

Tests use SQLite in-memory database. Configuration in `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

## 🏗️ Architecture

### Design Patterns

#### 1. **Repository Pattern**
- Eloquent models abstract database operations
- Clean separation between data access and business logic

#### 2. **Factory Pattern**
- `AbstractNewsSource` defines interface
- Concrete implementations for each news source
- Easy to add new sources

#### 3. **Service Layer**
- `NewsAggregatorService` orchestrates news fetching
- Business logic separated from controllers

#### 4. **API Resources**
- `ArticleResource` transforms model data
- Consistent API responses

### SOLID Principles

#### Single Responsibility Principle
- Each class has one responsibility
- `NewsApiSource` only handles NewsAPI integration
- `ArticleController` only handles HTTP requests

#### Open/Closed Principle
- System open for extension (new sources)
- Closed for modification (existing sources)

#### Liskov Substitution Principle
- All news sources can replace `AbstractNewsSource`
- Polymorphic behavior guaranteed

#### Interface Segregation Principle
- `NewsSourceInterface` defines minimal contract
- Classes implement only needed methods

#### Dependency Inversion Principle
- High-level modules depend on abstractions
- `NewsAggregatorService` depends on interface, not concrete classes

### Directory Structure

```
app/
├── Console/
│   ├── Commands/
│   │   └── FetchNews.php          # News fetching command
│   └── Kernel.php                 # Task scheduler
├── Contracts/
│   └── NewsSourceInterface.php    # News source contract
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── ArticleController.php
│   │       └── PreferenceController.php
│   ├── Middleware/
│   └── Resources/
│       └── ArticleResource.php    # API response transformer
├── Models/
│   ├── Article.php
│   ├── User.php
│   └── UserPreference.php
└── Services/
    ├── NewsAggregator/
    │   ├── AbstractNewsSource.php # Base news source
    │   ├── NewsApiSource.php
    │   ├── GuardianSource.php
    │   └── BbcSource.php
    └── NewsAggregatorService.php  # Service orchestrator

database/
├── factories/
│   ├── ArticleFactory.php
│   └── UserFactory.php
└── migrations/
    ├── create_articles_table.php
    └── create_user_preferences_table.php

tests/
└── Feature/
    └── Api/
        ├── ArticleApiTest.php
        └── PreferencesApiTest.php
```

## 🐛 Troubleshooting

### Common Issues

#### 1. API Key Errors

**Problem:** "Failed to fetch from [Source]"

**Solution:**
```bash
# Verify API keys in .env
php artisan config:clear
php artisan cache:clear
```

#### 2. Database Connection Issues

**Problem:** "SQLSTATE[HY000] [2002] Connection refused"

**Solution:**
```bash
# Check database credentials in .env
# Verify MySQL/PostgreSQL is running
sudo systemctl start mysql  # Linux
brew services start mysql   # macOS
```

#### 3. Scheduler Not Running

**Problem:** News not updating automatically

**Solution:**
```bash
# Verify cron job is set up
crontab -l

# Test scheduler manually
php artisan schedule:run

# Check logs
tail -f storage/logs/laravel.log
```

#### 4. Route Not Found

**Problem:** 404 on API endpoints

**Solution:**
```bash
php artisan route:clear
php artisan route:cache
php artisan route:list | grep api
```

#### 5. Factory Method Errors in Tests

**Problem:** "Call to undefined method Article::factory()"

**Solution:**
```bash
# Add HasFactory trait to models
# Clear autoload
composer dump-autoload
php artisan config:clear
```

### Debug Commands

```bash
# Clear all caches
php artisan optimize:clear

# View routes
php artisan route:list

# Check configuration
php artisan config:show

# View logs
tail -f storage/logs/laravel.log

# Database status
php artisan migrate:status

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Logging

Check logs for detailed error information:

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# PHP logs (location varies)
tail -f /var/log/php-fpm/error.log  # Linux
tail -f /usr/local/var/log/php-fpm.log  # macOS
```

## 📊 Performance Optimization

### Database Indexes

The application uses indexes on frequently queried columns:
- `published_at`
- `source`
- `category`
- Full-text index on `title` and `content`

### Caching Strategies

```php
// Cache article counts
Cache::remember('articles_count', 3600, function () {
    return Article::count();
});

// Cache filter options
Cache::remember('filter_options', 3600, function () {
    return [
        'sources' => Article::distinct()->pluck('source'),
        'categories' => Article::distinct()->pluck('category'),
    ];
});
```

### Query Optimization

```php
// Use pagination
$articles = Article::paginate(20);

// Eager loading (if adding relationships)
$articles = Article::with('comments')->get();

// Select specific columns
$articles = Article::select('id', 'title', 'published_at')->get();
```

## 🔐 Security

### API Rate Limiting

Add rate limiting to routes:

```php
// routes/api.php
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/articles', [ArticleController::class, 'index']);
});
```

### Input Validation

All inputs are validated:
```php
$request->validate([
    'sources' => 'nullable|array',
    'sources.*' => 'string',
]);
```

### SQL Injection Prevention

Using Eloquent ORM and parameterized queries:
```php
// Safe
Article::where('source', $source)->get();

// Unsafe (avoid)
DB::select("SELECT * FROM articles WHERE source = '$source'");
```

## 📝 API Rate Limits

### NewsAPI
- Free tier: 100 requests/day
- Development: 1,000 requests/day ($49/month)
- Business: 250,000 requests/day ($449/month)

### The Guardian
- Free tier: 5,000 requests/day
- Tier 1: 50,000 requests/day
- Enterprise: Custom limits

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Standards

- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation
- Use meaningful commit messages

## 📄 License

This project is licensed under the MIT License.

## 👥 Authors

- Chima Ejiofor - Initial work

## 🙏 Acknowledgments

- [NewsAPI](https://newsapi.org/) for news aggregation
- [The Guardian Open Platform](https://open-platform.theguardian.com/)
- Laravel community for excellent documentation



---

