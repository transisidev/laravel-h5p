# API Reference

## Services

### LaravelH5p

Main H5P service for content management.

```php
use Illuminate\Support\Facades\App;

$h5p = App::make('LaravelH5p');
```

#### Methods

```php
// Get content by ID
$content = $h5p->get_content($id);

// Get embed code for content
$embed = $h5p->get_embed($content, $settings);
// Returns: ['embed' => '...', 'settings' => [...]]

// Get editor settings
$settings = $h5p::get_editor();
```

### LrsService

Service for xAPI/LRS integration.

```php
use App\Services\LrsService;

$lrs = app(LrsService::class);
```

#### Methods

```php
// Check if LRS is enabled
$enabled = $lrs->isEnabled();

// Build xAPI statement
$statement = $lrs->buildCompletedStatement($user, $content, $result);

// Send statement to LRS
$success = $lrs->sendStatement($statement);
```

## Models

### H5pContent

```php
use Transisi\LaravelH5P\Eloquents\H5pContent;

// Get all content
$contents = H5pContent::all();

// Get content with results
$content = H5pContent::with('results')->find($id);
```

### H5pResult

```php
use Transisi\LaravelH5P\Eloquents\H5pResult;

// Get user results
$results = H5pResult::where('user_id', $userId)->get();

// Relationships
$result->user;    // User model
$result->content; // H5pContent model
```

## Events

### H5pResultSaved

Dispatched when a student result is saved.

```php
use App\Events\H5pResultSaved;

// Listen in EventServiceProvider
Event::listen(H5pResultSaved::class, function ($event) {
    $result = $event->result;
    // Handle event
});
```

## Jobs

### SendXapiStatement

Queue job for sending xAPI statements.

```php
use App\Jobs\SendXapiStatement;

SendXapiStatement::dispatch($result);
```
