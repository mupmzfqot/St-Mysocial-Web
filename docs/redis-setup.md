# Redis Setup & Configuration Guide

## Overview
Redis digunakan sebagai cache driver untuk mengoptimalkan performa API posts dengan cache yang intelligent dan auto-invalidation.

## Konfigurasi

### 1. Environment Variables (.env)
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CLIENT=predis
REDIS_CACHE_DB=1
```

### 2. Database Configuration (config/database.php)
```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'predis'),
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],
    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
],
```

## Installation

### 1. Install Predis
```bash
composer require predis/predis
```

### 2. Install Redis Server

#### macOS (Homebrew)
```bash
brew install redis
brew services start redis
```

#### Ubuntu/Debian
```bash
sudo apt update
sudo apt install redis-server
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

#### Windows
Download dari https://github.com/microsoftarchive/redis/releases

## Testing

### 1. Test Redis Connection
```bash
# Test Redis CLI
redis-cli ping
# Should return: PONG

# Test Laravel Redis
php artisan tinker
>>> Redis::ping()
# Should return: +PONG
```

### 2. Test Cache
```bash
php artisan tinker

# Test basic cache
>>> Cache::put('test', 'value', 60)
>>> Cache::get('test')
# Should return: "value"

# Test posts cache
>>> app(App\Services\PostCacheService::class)->getCachedPosts('public', null, 20, '')
```

### 3. Monitor Cache
```bash
# Basic monitoring
php artisan cache:monitor

# With statistics
php artisan cache:monitor --stats

# Clear cache
php artisan cache:monitor --clear
```

## Cache Keys Structure

### Posts Cache Keys
- **Pattern**: `{prefix}posts_{type}_s{signature}_{hash}`
- **Example**: `teamst_database_posts_public_s46f681e3d39d2034bc6e7da35680671a_98f13708210194c475687be6106a3b84`

### Signature Keys
- **Pattern**: `{prefix}signature_{type}`
- **Example**: `teamst_database_signature_public`

### Tag Keys
- **Pattern**: `{prefix}tag:posts_{type}:entries`
- **Example**: `teamst_database_tag:posts_public:entries`

## Monitoring Commands

### 1. Check Redis Keys
```bash
# All keys in database 1
redis-cli -n 1 keys "*"

# Posts cache keys
redis-cli -n 1 keys "*posts*"

# Signature keys
redis-cli -n 1 keys "*signature*"
```

### 2. Check TTL
```bash
# Check TTL for specific key
redis-cli -n 1 ttl "teamst_database_posts_public_s46f681e3d39d2034bc6e7da35680671a_98f13708210194c475687be6106a3b84"
```

### 3. Clear Cache
```bash
# Clear specific type
php artisan posts:clear-cache public

# Clear all cache
php artisan posts:clear-cache

# Clear Redis database
redis-cli -n 1 flushdb
```

## Performance Optimization

### 1. Cache TTL
- **Posts Cache**: 15 minutes (900 seconds)
- **Signature Cache**: 24 hours (86400 seconds)

### 2. Cache Invalidation
- **Automatic**: When post is created/updated/deleted
- **Manual**: Using artisan commands
- **Selective**: Only invalidates cache for specific post type

### 3. Cache Strategy
- **Stable Sorting**: `orderBy('created_at', 'desc')->orderBy('id', 'desc')`
- **Eager Loading**: `with(['author', 'media'])`
- **Query String**: Included in cache key for search functionality

## Troubleshooting

### 1. Redis Connection Failed
```bash
# Check Redis status
brew services list | grep redis  # macOS
sudo systemctl status redis-server  # Ubuntu

# Start Redis
brew services start redis  # macOS
sudo systemctl start redis-server  # Ubuntu
```

### 2. Cache Not Working
```bash
# Clear Laravel cache
php artisan cache:clear
php artisan config:clear

# Check cache driver
php artisan tinker
>>> config('cache.default')
```

### 3. Keys Not Found
```bash
# Check correct database
redis-cli -n 1 keys "*"  # Database 1 for cache
redis-cli -n 0 keys "*"  # Database 0 for default
```

## API Response with Cache

### Example Response
```json
{
    "error": 0,
    "data": [
        {
            "id": 1,
            "content": "Post content...",
            "author": {
                "id": 1,
                "name": "John Doe",
                "email": "john@example.com",
                "profile_img": "https://example.com/avatar.jpg"
            }
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 20,
        "total": 100,
        "has_more_pages": true,
        "next_page_url": "http://localhost/api/posts?page=2&per_page=20&type=public"
    },
    "meta": {
        "type": "public",
        "search_term": null,
        "per_page": 20,
        "query_string": "page=1&per_page=20&type=public"
    }
}
```

## Best Practices

1. **Monitor Cache Hit Rate**: Use `php artisan cache:monitor --stats`
2. **Clear Cache Regularly**: During maintenance windows
3. **Monitor Memory Usage**: Redis memory usage
4. **Backup Cache**: Important for production
5. **Set Appropriate TTL**: Balance between performance and freshness

## Production Considerations

1. **Redis Persistence**: Configure RDB/AOF for data persistence
2. **Memory Limits**: Set maxmemory and eviction policy
3. **Security**: Use Redis password in production
4. **Monitoring**: Use Redis INFO command for monitoring
5. **Backup**: Regular backups of Redis data 