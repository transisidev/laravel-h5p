# Laravel H5P

H5P Plugin for Laravel platform - Updated for Laravel 12.

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## ✨ Features

- 📦 H5P Content Management (Create, Edit, Delete, Export, Embed)
- 📚 Library Management (Upload, Restrict, Clear Cache)
- 📊 Student Results Tracking
- 🔗 xAPI/LRS Integration (via queue jobs)
- 🌍 Multi-language Support (EN, AR, FR)
- ⬆️ Chunked File Upload Support (large video/file uploads)
- 🎨 Publishable Views & Assets

## 📋 Requirements

- PHP 8.2+
- Laravel 12.x
- PostgreSQL or MySQL
- `h5p/h5p-core: ^1.24`
- `h5p/h5p-editor: ^1.24`
- `guzzlehttp/guzzle: ^7.0`

## 🚀 Installation

### 1. Install via Composer

```bash
composer require transisidev/laravel-h5p
```

### 2. Run Installation Command

```bash
php artisan h5p:install
```

This will:
- Publish configuration files
- Run database migrations
- Create storage directories
- Link storage to public

### 3. Check Installation Status

```bash
php artisan h5p:status
```

## ⚙️ Configuration

### Publish Config

```bash
php artisan vendor:publish --tag=laravel-h5p-config
```

Config file: `config/laravel-h5p.php`

### Key Configuration Options

| Key | Default | Description |
|-----|---------|-------------|
| `language` | `en` | Default language (`en`, `ar`, `fr`) |
| `use_router` | `ALL` | Route group to load: `ALL`, `EDITOR`, or `EXPORT` |
| `h5p_frame` | `true` | Show H5P frame/toolbar |
| `h5p_export` | `true` | Allow content export |
| `h5p_embed` | `true` | Allow content embed |
| `h5p_hub_is_enabled` | `true` | Enable H5P Hub integration |
| `h5p_save_content_state` | `false` | Save user content state |
| `h5p_save_content_frequency` | `30` | State save frequency (seconds) |
| `h5p_editor_max_post_size` | `128M` | Max POST size for editor uploads |
| `h5p_editor_chunk_upload_enabled` | `false` | Enable chunked uploads |
| `h5p_editor_chunk_threshold_bytes` | `5242880` | Chunk upload threshold (5 MB) |
| `h5p_editor_chunk_max_chunk_bytes` | `5242880` | Max chunk size (5 MB) |
| `h5p_editor_chunk_max_total_bytes` | `536870912` | Max total upload size (512 MB) |

### Environment Variables

Add to your `.env`:

```env
# H5P Chunked Upload (optional, for large files)
H5P_EDITOR_MAX_POST_SIZE=128M
H5P_EDITOR_CHUNK_UPLOAD_ENABLED=false
H5P_EDITOR_CHUNK_THRESHOLD_BYTES=5242880
H5P_EDITOR_CHUNK_MAX_CHUNK_BYTES=5242880
H5P_EDITOR_CHUNK_MAX_TOTAL_BYTES=536870912

# LRS/xAPI Integration (optional)
LRS_ENABLED=true
LRS_ENDPOINT=https://your-lrs.com/data/xAPI
LRS_USERNAME=your-key
LRS_PASSWORD=your-secret
```

## 📖 Available Commands

| Command | Description |
|---------|-------------|
| `php artisan h5p:install` | Full installation (publish config, run migrations, setup storage) |
| `php artisan h5p:publish --force` | Re-publish assets and config |
| `php artisan h5p:cleanup --temp` | Clean temporary files |
| `php artisan h5p:cleanup --unused` | Remove unused libraries |
| `php artisan h5p:status` | Show installation status |
| `php artisan h5p:migrate` | Run H5P database migrations manually |
| `php artisan h5p:reset` | Reset H5P data |

## 🔧 Usage

### Publish Assets

```bash
# Publish all assets
php artisan vendor:publish --tag=laravel-h5p-assets

# Publish views
php artisan vendor:publish --tag=laravel-h5p-views

# Publish language files
php artisan vendor:publish --tag=laravel-h5p-lang

# Publish migrations
php artisan vendor:publish --tag=laravel-h5p-migrations
```

### Available Routes

#### Editor & Library Routes (`use_router = EDITOR` or `ALL`, requires auth)

| Method | Route | Name | Description |
|--------|-------|------|-------------|
| GET | `/h5p/library` | `h5p.library.index` | List H5P libraries |
| GET | `/h5p/library/show/{id}` | `h5p.library.show` | Show a library |
| POST | `/h5p/library/store` | `h5p.library.store` | Upload a library |
| DELETE | `/h5p/library/destroy` | `h5p.library.destroy` | Delete a library |
| GET | `/h5p/library/restrict` | `h5p.library.restrict` | Restrict a library |
| POST | `/h5p/library/clear` | `h5p.library.clear` | Clear library cache |
| GET/POST | `/h5p` | `h5p.index` | List H5P content |
| GET | `/h5p/create` | `h5p.create` | Create new content |
| POST | `/h5p` | `h5p.store` | Store new content |
| GET | `/h5p/{id}` | `h5p.show` | Show content |
| GET | `/h5p/{id}/edit` | `h5p.edit` | Edit content |
| PUT | `/h5p/{id}` | `h5p.update` | Update content |
| DELETE | `/h5p/{id}` | `h5p.destroy` | Delete content |

#### Ajax Routes

| Method | Route | Name | Description |
|--------|-------|------|-------------|
| GET/POST | `/ajax/libraries` | `h5p.ajax.libraries` | Get libraries list |
| GET | `/ajax/single-libraries` | `h5p.ajax.single-libraries` | Get single library |
| GET/POST | `/ajax/content-type-cache` | `h5p.ajax.content-type-cache` | Content type cache |
| GET/POST | `/ajax/content-hub-metadata-cache` | `h5p.ajax.content-hub-metadata-cache` | Hub metadata cache |
| POST | `/ajax/library-install` | `h5p.ajax.library-install` | Install library |
| POST | `/ajax/library-upload` | `h5p.ajax.library-upload` | Upload library |
| POST | `/ajax/rebuild-cache` | `h5p.ajax.rebuild-cache` | Rebuild asset cache |
| POST | `/ajax/files` | `h5p.ajax.files` | File upload |
| GET | `/ajax/finish` | `h5p.ajax.finish` | Finish action |
| POST | `/ajax/content-user-data` | `h5p.ajax.content-user-data` | Save user data |
| POST | `/ajax/files/chunk` | `h5p.ajax.files.chunk` | Chunked file upload |
| POST | `/ajax/files/chunk-assemble` | `h5p.ajax.files.chunk-assemble` | Assemble chunks |

#### Export & Embed Routes (always available)

| Method | Route | Name | Description |
|--------|-------|------|-------------|
| GET | `/h5p/embed/{id}` | `h5p.embed` | Embed H5P content |
| GET | `/h5p/export/{id}` | `h5p.export` | Download/export H5P content |

### Programmatic Usage

```php
use Illuminate\Support\Facades\App;

// Get H5P instance
$h5p = App::make('LaravelH5p');

// Get content by ID
$content = $h5p->get_content($id);

// Get embed code
$embed = $h5p->get_embed($content, $settings);
// Returns: ['embed' => '...', 'settings' => [...]]

// Get editor settings
$settings = $h5p::get_editor();
```

### Facade Usage

```php
use Transisi\LaravelH5P\Facades\LaravelH5p;

$content = LaravelH5p::get_content($id);
```

## 📦 Eloquent Models

| Model | Namespace | Description |
|-------|-----------|-------------|
| `H5pContent` | `Transisi\LaravelH5P\Eloquents` | H5P content entries |
| `H5pLibrary` | `Transisi\LaravelH5P\Eloquents` | Installed libraries |
| `H5pResult` | `Transisi\LaravelH5P\Eloquents` | Student results |
| `H5pContentsLibrary` | `Transisi\LaravelH5P\Eloquents` | Content-library pivot |
| `H5pContentsTag` | `Transisi\LaravelH5P\Eloquents` | Content tags |
| `H5pContentsUserData` | `Transisi\LaravelH5P\Eloquents` | User content state |
| `H5pEventLog` | `Transisi\LaravelH5P\Eloquents` | Event logs |
| `H5pLibrariesHubCache` | `Transisi\LaravelH5P\Eloquents` | H5P Hub cache |

```php
use Transisi\LaravelH5P\Eloquents\H5pContent;
use Transisi\LaravelH5P\Eloquents\H5pResult;

// Get all content
$contents = H5pContent::all();

// Get content with results
$content = H5pContent::with('results')->find($id);

// Get results for a user
$results = H5pResult::where('user_id', $userId)->get();
```

## 🔗 xAPI/LRS Integration

When LRS is enabled, student results can be sent to your LRS via queue jobs:

1. Set LRS credentials in `.env`
2. Dispatch the `SendXapiStatement` job after saving a result:

```php
use App\Jobs\SendXapiStatement;

SendXapiStatement::dispatch($result);
```

3. Run queue worker:

```bash
php artisan queue:work
```

## 📁 Directory Structure

```
storage/app/public/h5p/
├── content/      # H5P content files
├── libraries/    # H5P libraries
└── temp/         # Temporary upload files

public/assets/vendor/
├── laravel-h5p/  # Package assets
├── h5p/h5p-core/ # H5P core JS/CSS/fonts/images
└── h5p/h5p-editor/ # H5P editor assets

resources/views/vendor/laravel-h5p/  # Published views
```

## 🌍 Multi-language Support

Language files are available for:
- **English** (`en`)
- **Arabic** (`ar`)
- **French** (`fr`)

Set the default language in `config/laravel-h5p.php`:

```php
'language' => 'en',
```

Or publish and customize translations:

```bash
php artisan vendor:publish --tag=laravel-h5p-lang
```

## 🤝 Contributing

Contributions are welcome! Please open an issue or submit a pull request.

## 📄 License

MIT License — see [LICENSE](LICENSE) file.
