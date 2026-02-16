# Práctica Guiada - RA9: Aplicaciones Web Híbridas
## Desarrollo de Aplicaciones Web - Desarrollo Web en Entorno Servidor

---

## Arquitectura de la Solución

En esta práctica vas a ampliar tu aplicación ePortfolio con las siguientes funcionalidades:

1. **Importación de datos** desde fuentes externas (JSON Resume API, GitHub)
2. **Análisis de competencias** más demandadas usando visualización de datos
3. **Exportación de portfolios** a formatos estándar
4. **API pública** para compartir portfolios

---

## Tecnologías y Librerías

### Librerías principales que usaremos:

1. **Guzzle HTTP Client** - Cliente HTTP para consumir APIs
2. **Laravel Charts (ConsoleTVs/Charts)** - Visualización de datos
3. **Laravel Excel (Maatwebsite)** - Exportación de datos
4. **Laravel Sanctum** - Autenticación API
5. **Barryvdh/DomPDF** - Generación de PDFs

---

### Librerías principales que usaremos:

1. **Guzzle HTTP Client** - Cliente HTTP para consumir APIs
2. **Laravel Charts (ConsoleTVs/Charts)** - Visualización de datos
3. **Laravel Excel (Maatwebsite)** - Exportación de datos
4. **Laravel Sanctum** - Autenticación API
5. **Barryvdh/DomPDF** - Generación de PDFs

---

## Preparación del entorno

A partir del punto en el que cada uno de los estudiantes se haya quedado en la replicación del ePortfolio, vamos a crear una rama llamada `aplicacionesHibridas`.

```bash
git switch -C aplicacionesHibridas
```

---

## PARTE 1: Consumo de APIs Externas (2 horas)

### Sesión 1.1: Instalación de Dependencias y Configuración

#### Paso 1.1: Instalar Guzzle HTTP Client

```bash
composer require guzzlehttp/guzzle
```

---

### Sesión 1.2: Crear Servicio de Importación desde JSON Resume

#### Contexto: ¿Qué es un Servicio en Laravel?

Un Servicio en Laravel es una clase que encapsula la lógica de negocio de una funcionalidad específica. Se utiliza para organizar el código y hacerlo más reutilizable y fácil de mantener. Los Servicios suelen contener métodos que realizan tareas específicas y pueden ser inyectados en controladores, comandos y otros lugares donde se necesiten.

Suelen nombrarse con un sufijo “Service”, como “ResumeImportService”, y se ubican en el directorio `app/Services`.

#### Contexto: ¿Qué es JSON Resume?

**JSON Resume** es un estándar abierto para currículums en formato JSON. Permite importar/exportar datos de CV de forma estructurada.

Ejemplo de formato JSON Resume:
```json
{
  "basics": {
    "name": "Juan Pérez",
    "label": "Desarrollador Full Stack",
    "email": "juan@ejemplo.com",
    "phone": "612345678",
    "summary": "Desarrollador con 5 años de experiencia..."
  },
  "work": [
    {
      "company": "Tech Corp",
      "position": "Developer",
      "startDate": "2020-01-01",
      "endDate": "2023-12-31",
      "summary": "Desarrollo de aplicaciones web"
    }
  ],
  "skills": [
    {
      "name": "PHP",
      "level": "Advanced",
      "keywords": ["Laravel", "Symfony"]
    }
  ]
}
```

#### Paso 1.3: Crear el Servicio de Importación

Crea un nuevo servicio en `app/Services/ResumeImportService.php`:

```php
<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class ResumeImportService
{
    protected Client $client;
    
    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 10.0,
            'verify' => true,
        ]);
    }
    
    /**
     * Importar datos desde una URL de JSON Resume
     * 
     * @param string $url URL del JSON Resume
     * @return array|null
     */
    public function importFromJsonResume(string $url): ?array
    {
        try {
            $response = $this->client->get($url);
            
            if ($response->getStatusCode() !== 200) {
                Log::error('Error al obtener JSON Resume', [
                    'status' => $response->getStatusCode(),
                    'url' => $url
                ]);
                return null;
            }
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            // Validar que tiene la estructura básica de JSON Resume
            if (!isset($data['basics'])) {
                Log::error('JSON no tiene formato JSON Resume válido');
                return null;
            }
            
            return $this->transformJsonResumeData($data);
            
        } catch (GuzzleException $e) {
            Log::error('Excepción al importar JSON Resume', [
                'error' => $e->getMessage(),
                'url' => $url
            ]);
            return null;
        }
    }
    
    /**
     * Transforma datos de JSON Resume al formato del ePortfolio
     * 
     * @param array $jsonResumeData
     * @return array
     */
    protected function transformJsonResumeData(array $jsonResumeData): array
    {
        $transformed = [
            'personal_info' => [
                'name' => $jsonResumeData['basics']['name'] ?? '',
                'email' => $jsonResumeData['basics']['email'] ?? '',
                'phone' => $jsonResumeData['basics']['phone'] ?? '',
                'summary' => $jsonResumeData['basics']['summary'] ?? '',
                'location' => $jsonResumeData['basics']['location']['city'] ?? '',
            ],
            'work_experience' => [],
            'education' => [],
            'skills' => [],
        ];
        
        // Transformar experiencia laboral
        if (isset($jsonResumeData['work'])) {
            foreach ($jsonResumeData['work'] as $work) {
                $transformed['work_experience'][] = [
                    'company' => $work['company'] ?? '',
                    'position' => $work['position'] ?? '',
                    'start_date' => $work['startDate'] ?? null,
                    'end_date' => $work['endDate'] ?? null,
                    'description' => $work['summary'] ?? '',
                ];
            }
        }
        
        // Transformar educación
        if (isset($jsonResumeData['education'])) {
            foreach ($jsonResumeData['education'] as $edu) {
                $transformed['education'][] = [
                    'institution' => $edu['institution'] ?? '',
                    'area' => $edu['area'] ?? '',
                    'study_type' => $edu['studyType'] ?? '',
                    'start_date' => $edu['startDate'] ?? null,
                    'end_date' => $edu['endDate'] ?? null,
                ];
            }
        }
        
        // Transformar habilidades
        if (isset($jsonResumeData['skills'])) {
            foreach ($jsonResumeData['skills'] as $skill) {
                $transformed['skills'][] = [
                    'name' => $skill['name'] ?? '',
                    'level' => $skill['level'] ?? 'Intermediate',
                    'keywords' => $skill['keywords'] ?? [],
                ];
            }
        }
        
        return $transformed;
    }
    
    /**
     * Importar datos desde el perfil público de GitHub
     * 
     * @param string $username
     * @return array|null
     */
    public function importFromGitHub(string $username): ?array
    {
        try {
            // Obtener datos del perfil
            $profileResponse = $this->client->get(
                "https://api.github.com/users/{$username}",
                [
                    'headers' => [
                        'Accept' => 'application/vnd.github.v3+json',
                        'User-Agent' => 'ePortfolio-Laravel-App'
                    ]
                ]
            );
            
            $profileData = json_decode($profileResponse->getBody()->getContents(), true);
            
            // Obtener repositorios
            $reposResponse = $this->client->get(
                "https://api.github.com/users/{$username}/repos?sort=updated&per_page=10",
                [
                    'headers' => [
                        'Accept' => 'application/vnd.github.v3+json',
                        'User-Agent' => 'ePortfolio-Laravel-App'
                    ]
                ]
            );
            
            $repos = json_decode($reposResponse->getBody()->getContents(), true);
            
            return $this->transformGitHubData($profileData, $repos);
            
        } catch (GuzzleException $e) {
            Log::error('Error al importar desde GitHub', [
                'error' => $e->getMessage(),
                'username' => $username
            ]);
            return null;
        }
    }
    
    /**
     * Transforma datos de GitHub al formato del ePortfolio
     */
    protected function transformGitHubData(array $profile, array $repos): array
    {
        $projects = [];
        
        foreach ($repos as $repo) {
            $projects[] = [
                'title' => $repo['name'],
                'description' => $repo['description'] ?? 'Sin descripción',
                'url' => $repo['html_url'],
                'technologies' => [$repo['language'] ?? 'N/A'],
                'stars' => $repo['stargazers_count'],
                'updated_at' => $repo['updated_at'],
            ];
        }
        
        return [
            'profile' => [
                'name' => $profile['name'] ?? $profile['login'],
                'bio' => $profile['bio'] ?? '',
                'location' => $profile['location'] ?? '',
                'avatar_url' => $profile['avatar_url'] ?? '',
                'github_url' => $profile['html_url'],
                'public_repos' => $profile['public_repos'],
            ],
            'projects' => $projects,
        ];
    }
}
```

** Análisis del código:**

1. **Constructor**: Inicializa el cliente Guzzle con timeout de 10 segundos
2. **importFromJsonResume**: Consume una URL JSON Resume y valida la estructura
3. **transformJsonResumeData**: Convierte el formato JSON Resume al modelo de datos de tu ePortfolio
4. **importFromGitHub**: Consume la API pública de GitHub (no requiere autenticación)
5. **Manejo de errores**: Usa try-catch y logging para debugging

#### Paso 1.4: Crear el Controlador de Importación

Crea `app/Http/Controllers/PortfolioImportController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\ResumeImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PortfolioImportController extends Controller
{
    protected ResumeImportService $importService;
    
    public function __construct(ResumeImportService $importService)
    {
        $this->importService = $importService;
    }
    
    /**
     * Mostrar formulario de importación
     */
    public function showImportForm()
    {
        return view('portfolio.import');
    }
    
    /**
     * Importar desde JSON Resume
     */
    public function importJsonResume(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'json_resume_url' => 'required|url',
        ]);
        
        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $data = $this->importService->importFromJsonResume(
            $request->input('json_resume_url')
        );
        
        if (!$data) {
            return back()->with('error', 'No se pudo importar el JSON Resume. Verifica la URL y el formato.');
        }
        
        // Aquí deberías guardar los datos en tu base de datos
        // Este es un ejemplo simplificado
        try {
            DB::beginTransaction();
            
            // Guardar información personal
            // Nota: Adapta esto a tu modelo de datos
            $user = Auth::user();
            $user->update([
                'name' => $data['personal_info']['name'],
                'email' => $data['personal_info']['email'],
                // ... otros campos
            ]);
            
            // Guardar experiencia laboral, educación, habilidades, etc.
            // Aquí usarías tus modelos Eloquent
            
            DB::commit();
            
            return redirect()
                ->route('portfolio.index')
                ->with('success', 'Portfolio importado correctamente desde JSON Resume');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Error al guardar los datos: ' . $e->getMessage());
        }
    }
    
    /**
     * Importar desde GitHub
     */
    public function importGitHub(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'github_username' => 'required|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $data = $this->importService->importFromGitHub(
            $request->input('github_username')
        );
        
        if (!$data) {
            return back()->with('error', 'No se pudo importar desde GitHub. Verifica el usuario.');
        }
        
        try {
            DB::beginTransaction();
            
            // Guardar proyectos de GitHub
            // Adapta esto a tu modelo de datos
            foreach ($data['projects'] as $projectData) {
                // crea una evidencia por cada proyecto
                // asociada a la tarea con id = 1
                // pertenenciente al usuario autenticado
                // y con estado "pendiente"
            }
            
            DB::commit();
            
            return redirect()
                ->route('portfolio.index')
                ->with('success', "Se importaron " . count($data['projects']) . " proyectos desde GitHub");
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Error al guardar proyectos: ' . $e->getMessage());
        }
    }
}
```

#### Paso 1.5: Crear las Rutas

Añade en `routes/web.php`:

```php
use App\Http\Controllers\PortfolioImportController;

Route::middleware(['auth'])->group(function () {
    // Formulario de importación
    Route::get('/portfolio/import', [PortfolioImportController::class, 'showImportForm'])
        ->name('portfolio.import.form');
    
    // Importar desde JSON Resume
    Route::post('/portfolio/import/json-resume', [PortfolioImportController::class, 'importJsonResume'])
        ->name('portfolio.import.json-resume');
    
    // Importar desde GitHub
    Route::post('/portfolio/import/github', [PortfolioImportController::class, 'importGitHub'])
        ->name('portfolio.import.github');
});
```

#### Paso 1.6: Crear la Vista de Importación

Crea `resources/views/portfolio/import.blade.php`:

```blade
@extends('layouts.master')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <h2 class="mb-4">Importar Portfolio</h2>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <div class="row">
                <!-- Card: Importar desde JSON Resume -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-file-earmark-code"></i> JSON Resume
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Importa tu curriculum desde un archivo JSON Resume compatible.
                            </p>
                            
                            <form method="POST" action="{{ route('portfolio.import.json-resume') }}">
                                @csrf
                                
                                <div class="mb-3">
                                    <label for="json_resume_url" class="form-label">
                                        URL del JSON Resume
                                    </label>
                                    <input 
                                        type="url" 
                                        class="form-control @error('json_resume_url') is-invalid @enderror" 
                                        id="json_resume_url" 
                                        name="json_resume_url"
                                        placeholder="https://ejemplo.com/resume.json"
                                        value="{{ old('json_resume_url') }}"
                                        required
                                    >
                                    @error('json_resume_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Ejemplo: <code>https://gist.githubusercontent.com/username/id/raw/resume.json</code>
                                    </small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-download"></i> Importar desde JSON Resume
                                </button>
                            </form>
                            
                            <hr class="my-3">
                            
                            <div class="alert alert-info mb-0">
                                <strong>ℹ️ ¿Qué es JSON Resume?</strong>
                                <p class="mb-0 small">
                                    JSON Resume es un estándar abierto para currículums.
                                    <a href="https://jsonresume.org/schema/" target="_blank">Ver especificación</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Card: Importar desde GitHub -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-github"></i> GitHub
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Importa tus proyectos desde tu perfil público de GitHub.
                            </p>
                            
                            <form method="POST" action="{{ route('portfolio.import.github') }}">
                                @csrf
                                
                                <div class="mb-3">
                                    <label for="github_username" class="form-label">
                                        Usuario de GitHub
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">@</span>
                                        <input 
                                            type="text" 
                                            class="form-control @error('github_username') is-invalid @enderror" 
                                            id="github_username" 
                                            name="github_username"
                                            placeholder="tu-usuario"
                                            value="{{ old('github_username') }}"
                                            required
                                        >
                                        @error('github_username')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="form-text text-muted">
                                        Se importarán tus 10 repositorios más recientes
                                    </small>
                                </div>
                                
                                <button type="submit" class="btn btn-dark w-100">
                                    <i class="bi bi-download"></i> Importar desde GitHub
                                </button>
                            </form>
                            
                            <hr class="my-3">
                            
                            <div class="alert alert-warning mb-0">
                                <strong>⚠️ Nota:</strong>
                                <p class="mb-0 small">
                                    Solo se importarán repositorios públicos. La API de GitHub
                                    tiene un límite de 60 peticiones por hora sin autenticación.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ventajas de la Reutilización -->
            <div class="card mt-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-recycle"></i> Ventajas de la Reutilización de Datos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6><i class="bi bi-clock-history text-success"></i> Ahorro de Tiempo</h6>
                            <p class="small">
                                No necesitas volver a introducir información que ya existe en otros servicios.
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="bi bi-check-circle text-success"></i> Consistencia</h6>
                            <p class="small">
                                Mantén tus datos actualizados en múltiples plataformas desde una única fuente.
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="bi bi-gear text-success"></i> Interoperabilidad</h6>
                            <p class="small">
                                Usa estándares abiertos como JSON Resume para facilitar la portabilidad de datos.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

#### Checkpoint Parte 1

**Prueba tu implementación:**

1. Accede a `/portfolio/import`
2. Prueba importar desde un JSON Resume de ejemplo:
   - Puedes usar: `https://gist.githubusercontent.com/thomasdavis/c9dcfa1b37dec07fb2ee7f36d7278105/raw/bbe30ca7a0743395fbbe4efbfd68ffd78a289135/resume.json`
3. Verifica que los datos se transforman correctamente
4. Revisa los logs en `storage/logs/laravel.log` para debugging

**Criterios evaluados en esta parte:**
- ✅ **Criterio a)** Ventajas de reutilización de código
- ✅ **Criterio b)** Tecnologías identificadas (Guzzle, APIs REST)
- ✅ **Criterio c)** Recuperación de repositorios existentes
- ✅ **Criterio e)** Uso de librerías (Guzzle)
- ✅ **Criterio f)** Uso de código de terceros (APIs)

---

## PARTE 2: Análisis y Visualización de Datos (2 horas)

### Sesión 2.1: Instalación de Laravel Charts

#### Paso 2.1: Instalar ConsoleTVs/Charts

```bash
composer update
composer require consoletvs/charts
php artisan vendor:publish --tag=charts_config
```

Preparamos las clases correspondientes a los gráficos que vamos a crear en `app/Charts/SkillsChart.php`:

```bash
php artisan make:chart MostDemandedSkills

php artisan make:chart SkillLevelDistribution
```


#### Paso 2.2: Preparar la Base de Datos

Antes de implementar el sistema de análisis, necesitamos crear la estructura de base de datos para las habilidades (`skills`). Como un usuario puede tener múltiples habilidades y una habilidad puede ser compartida por múltiples usuarios, implementaremos una **relación Many-to-Many (N:M)**.

##### Estructura de Relaciones

```
users (n) ←→ skill_user (pivot) ←→ (n) skills
```

- **skills**: Catálogo de habilidades disponibles (PHP, JavaScript, etc.)
- **skill_user**: Tabla pivot que relaciona usuarios con skills e incluye el nivel
- **users**: Usuarios del sistema (ya existente)

---

##### 2.2.1: Crear Migración de la Tabla `skills`

Ejecuta el siguiente comando para crear la migración:

```bash
php artisan make:migration create_skills_table
```

Edita el archivo generado en `database/migrations/YYYY_MM_DD_HHMMSS_create_skills_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->json('keywords')->nullable();
            $table->timestamps();
            
            // Índice para búsquedas rápidas por nombre
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};
```

**Explicación de los campos:**
- `id`: Identificador único de la habilidad
- `name`: Nombre de la habilidad (ej: "PHP", "JavaScript") - único
- `keywords`: Palabras clave relacionadas en formato JSON
- `timestamps`: created_at y updated_at

---

##### 2.2.2: Crear Migración de la Tabla Pivot `skill_user`

Ejecuta el comando:

```bash
php artisan make:migration create_skill_user_table
```

Edita el archivo generado en `database/migrations/YYYY_MM_DD_HHMMSS_create_skill_user_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('skill_user', function (Blueprint $table) {
            $table->id();
            
            // Claves foráneas
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            
            $table->foreignId('skill_id')
                ->constrained('skills')
                ->onDelete('cascade');
            
            // Nivel de competencia en esta skill para este usuario
            $table->enum('level', ['Beginner', 'Intermediate', 'Advanced', 'Expert'])
                ->default('Intermediate');
            
            $table->timestamps();
            
            // Índices compuestos para optimizar consultas
            $table->unique(['user_id', 'skill_id']); // Un usuario no puede tener la misma skill duplicada
            $table->index(['skill_id', 'level']); // Para estadísticas por skill y nivel
            $table->index('created_at'); // Para análisis de tendencias temporales
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_user');
    }
};
```

**Explicación de los campos:**
- `user_id`: Referencia al usuario
- `skill_id`: Referencia a la habilidad
- `level`: Nivel de competencia del usuario en esta habilidad específica
- `timestamps`: Para análisis de tendencias temporales

---

##### 2.2.3: Ejecutar las Migraciones

```bash
php artisan migrate
```

Deberías ver:

```
Migrating: YYYY_MM_DD_HHMMSS_create_skills_table
Migrated:  YYYY_MM_DD_HHMMSS_create_skills_table (XX.XXms)
Migrating: YYYY_MM_DD_HHMMSS_create_skill_user_table
Migrated:  YYYY_MM_DD_HHMMSS_create_skill_user_table (XX.XXms)
```

---

##### 2.2.4: Crear el Modelo `Skill`

Ejecuta:

```bash
php artisan make:model Skill
```

Edita `app/Models/Skill.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'keywords',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'keywords' => 'array', // Convierte automáticamente JSON a array
    ];

    /**
     * Relación Many-to-Many con Users
     * 
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'skill_user')
            ->withPivot('level')
            ->withTimestamps();
    }

    /**
     * Scope: Filtrar por nombre de skill
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * Obtener el número de usuarios que tienen esta skill
     * 
     * @return int
     */
    public function getUsersCountAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * Obtener la distribución de niveles para esta skill
     * 
     * @return array
     */
    public function getLevelDistribution(): array
    {
        return $this->users()
            ->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();
    }
}
```

**Características del modelo:**
- **Cast automático**: El campo `keywords` se convierte automáticamente de JSON a array PHP
- **Relación BelongsToMany**: Define la relación N:M con usuarios
- **withPivot**: Incluye el campo `level` de la tabla pivot
- **Scopes**: Métodos útiles para filtrar y analizar
- **Atributos calculados**: Para estadísticas

---

##### 2.2.5: Actualizar el Modelo `User`

Edita `app/Models/User.php` y añade la relación con skills:

```php
<?php

namespace App\Models;

// ... imports existentes ...
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    // ... código existente ...

    /**
     * Relación Many-to-Many con Skills
     * 
     * @return BelongsToMany
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'skill_user')
            ->withPivot('level')
            ->withTimestamps();
    }

    /**
     * Obtener las skills agrupadas por nivel
     * 
     * @return array
     */
    public function getSkillsByLevel(): array
    {
        return $this->skills()
            ->get()
            ->groupBy('pivot.level')
            ->map(fn($skills) => $skills->pluck('name')->toArray())
            ->toArray();
    }

    /**
     * Verificar si el usuario tiene una skill específica
     * 
     * @param string $skillName
     * @return bool
     */
    public function hasSkill(string $skillName): bool
    {
        return $this->skills()->where('name', $skillName)->exists();
    }

    /**
     * Obtener el nivel del usuario en una skill específica
     * 
     * @param string $skillName
     * @return string|null
     */
    public function getSkillLevel(string $skillName): ?string
    {
        $skill = $this->skills()->where('name', $skillName)->first();
        return $skill?->pivot->level;
    }
}
```

---

##### 2.2.6: Crear el Factory `SkillFactory`

Ejecuta:

```bash
php artisan make:factory SkillFactory
```

Edita `database/factories/SkillFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Skill>
 */
class SkillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Lista de skills técnicas comunes
        $technicalSkills = [
            'PHP' => ['Laravel', 'Symfony', 'CodeIgniter', 'Backend', 'Web Development'],
            'JavaScript' => ['React', 'Vue.js', 'Node.js', 'Frontend', 'Web Development'],
            'TypeScript' => ['Angular', 'React', 'Type Safety', 'Frontend'],
            'Python' => ['Django', 'Flask', 'Data Science', 'Machine Learning', 'Backend'],
            'Java' => ['Spring Boot', 'Enterprise', 'Backend', 'Android'],
            'C#' => ['.NET', 'ASP.NET', 'Backend', 'Windows'],
            'Ruby' => ['Rails', 'Backend', 'Web Development'],
            'Go' => ['Microservices', 'Backend', 'Performance'],
            'Rust' => ['Systems Programming', 'Performance', 'Safety'],
            'SQL' => ['MySQL', 'PostgreSQL', 'Database', 'Queries'],
            'NoSQL' => ['MongoDB', 'Redis', 'Database', 'Big Data'],
            'HTML' => ['Web', 'Frontend', 'Markup'],
            'CSS' => ['Styling', 'Frontend', 'Design'],
            'React' => ['Frontend', 'SPA', 'JavaScript', 'UI'],
            'Vue.js' => ['Frontend', 'SPA', 'JavaScript', 'Progressive'],
            'Angular' => ['Frontend', 'SPA', 'TypeScript', 'Enterprise'],
            'Laravel' => ['PHP', 'Backend', 'MVC', 'Eloquent'],
            'Django' => ['Python', 'Backend', 'MVC', 'ORM'],
            'Spring Boot' => ['Java', 'Backend', 'Microservices'],
            'Docker' => ['DevOps', 'Containers', 'Deployment'],
            'Kubernetes' => ['DevOps', 'Orchestration', 'Containers'],
            'Git' => ['Version Control', 'Collaboration', 'DevOps'],
            'AWS' => ['Cloud', 'Infrastructure', 'DevOps'],
            'Azure' => ['Cloud', 'Microsoft', 'Infrastructure'],
            'TailwindCSS' => ['CSS', 'Frontend', 'Utility-First'],
            'Bootstrap' => ['CSS', 'Frontend', 'Responsive'],
            'REST API' => ['Backend', 'Web Services', 'HTTP'],
            'GraphQL' => ['API', 'Query Language', 'Backend'],
            'Testing' => ['Quality Assurance', 'TDD', 'BDD'],
            'Agile' => ['Methodology', 'Scrum', 'Project Management'],
        ];

        $skillName = $this->faker->randomElement(array_keys($technicalSkills));
        
        return [
            'name' => $skillName,
            'keywords' => $technicalSkills[$skillName],
        ];
    }

    /**
     * Crear una skill específica
     * 
     * @param string $name
     * @param array $keywords
     * @return Factory
     */
    public function withName(string $name, array $keywords = []): Factory
    {
        return $this->state(function (array $attributes) use ($name, $keywords) {
            return [
                'name' => $name,
                'keywords' => $keywords,
            ];
        });
    }
}
```

---

##### 2.2.7: Crear el Seeder `SkillSeeder`

Ejecuta:

```bash
php artisan make:seeder SkillSeeder
```

Edita `database/seeders/SkillSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar datos existentes (opcional, comentar si no quieres borrar)
        DB::table('skill_user')->delete();
        DB::table('skills')->delete();

        // 1. Crear catálogo de skills base
        $skillsData = [
            // Backend
            ['name' => 'PHP', 'keywords' => ['Laravel', 'Symfony', 'Backend', 'Web Development']],
            ['name' => 'Laravel', 'keywords' => ['PHP', 'Framework', 'MVC', 'Eloquent', 'Artisan']],
            ['name' => 'Node.js', 'keywords' => ['JavaScript', 'Backend', 'Express', 'Async']],
            ['name' => 'Python', 'keywords' => ['Django', 'Flask', 'Backend', 'Data Science']],
            ['name' => 'Java', 'keywords' => ['Spring Boot', 'Enterprise', 'JVM', 'Backend']],
            ['name' => 'C#', 'keywords' => ['.NET', 'ASP.NET', 'Backend', 'Microsoft']],
            
            // Frontend
            ['name' => 'JavaScript', 'keywords' => ['Frontend', 'Web', 'ES6', 'Async']],
            ['name' => 'TypeScript', 'keywords' => ['JavaScript', 'Type Safety', 'Frontend']],
            ['name' => 'React', 'keywords' => ['Frontend', 'SPA', 'Components', 'Hooks']],
            ['name' => 'Vue.js', 'keywords' => ['Frontend', 'SPA', 'Progressive', 'Reactive']],
            ['name' => 'Angular', 'keywords' => ['Frontend', 'SPA', 'TypeScript', 'Enterprise']],
            ['name' => 'HTML', 'keywords' => ['Web', 'Markup', 'Frontend', 'Semantic']],
            ['name' => 'CSS', 'keywords' => ['Styling', 'Frontend', 'Responsive', 'Flexbox']],
            ['name' => 'TailwindCSS', 'keywords' => ['CSS', 'Utility-First', 'Frontend']],
            ['name' => 'Bootstrap', 'keywords' => ['CSS', 'Framework', 'Responsive']],
            
            // Database
            ['name' => 'MySQL', 'keywords' => ['SQL', 'Database', 'Relational', 'RDBMS']],
            ['name' => 'PostgreSQL', 'keywords' => ['SQL', 'Database', 'ACID', 'Advanced']],
            ['name' => 'MongoDB', 'keywords' => ['NoSQL', 'Database', 'Document', 'JSON']],
            ['name' => 'Redis', 'keywords' => ['NoSQL', 'Cache', 'In-Memory', 'Performance']],
            
            // DevOps & Tools
            ['name' => 'Docker', 'keywords' => ['Containers', 'DevOps', 'Deployment']],
            ['name' => 'Kubernetes', 'keywords' => ['Orchestration', 'DevOps', 'Containers']],
            ['name' => 'Git', 'keywords' => ['Version Control', 'GitHub', 'GitLab', 'Collaboration']],
            ['name' => 'CI/CD', 'keywords' => ['DevOps', 'Automation', 'Pipeline', 'Jenkins']],
            ['name' => 'AWS', 'keywords' => ['Cloud', 'Infrastructure', 'EC2', 'S3']],
            ['name' => 'Azure', 'keywords' => ['Cloud', 'Microsoft', 'Infrastructure']],
            
            // APIs & Architecture
            ['name' => 'REST API', 'keywords' => ['Web Services', 'HTTP', 'JSON', 'Backend']],
            ['name' => 'GraphQL', 'keywords' => ['API', 'Query Language', 'Flexible']],
            ['name' => 'Microservices', 'keywords' => ['Architecture', 'Distributed', 'Scalability']],
            
            // Testing & Methodologies
            ['name' => 'Testing', 'keywords' => ['QA', 'PHPUnit', 'Jest', 'TDD']],
            ['name' => 'Agile', 'keywords' => ['Scrum', 'Kanban', 'Methodology']],
        ];

        foreach ($skillsData as $skillData) {
            Skill::create($skillData);
        }

        $this->command->info('✓ Catálogo de ' . count($skillsData) . ' skills creado correctamente');

        // 2. Crear usuarios de prueba (si no existen)
        $existingUsersCount = User::count();
        
        if ($existingUsersCount < 25) {
            $usersToCreate = 25 - $existingUsersCount;
            User::factory($usersToCreate)->create();
            $this->command->info("✓ {$usersToCreate} usuarios de prueba creados");
        }

        // 3. Asignar skills a usuarios con distribución realista
        $users = User::all();
        $skills = Skill::all();
        $levels = ['Beginner', 'Intermediate', 'Advanced', 'Expert'];
        
        // Distribución de niveles (más realista)
        $levelDistribution = [
            'Beginner' => 30,      // 30%
            'Intermediate' => 40,  // 40%
            'Advanced' => 20,      // 20%
            'Expert' => 10,        // 10%
        ];

        // Crear un array ponderado de niveles
        $weightedLevels = [];
        foreach ($levelDistribution as $level => $percentage) {
            $weightedLevels = array_merge(
                $weightedLevels,
                array_fill(0, $percentage, $level)
            );
        }

        // Distribuir las skills en los últimos 6 meses para ver tendencias
        $sixMonthsAgo = now()->subMonths(6);
        
        $totalAssignments = 0;
        
        foreach ($users as $user) {
            // Cada usuario tendrá entre 5 y 12 skills
            $numSkills = rand(5, 12);
            
            // Seleccionar skills aleatorias sin repetir
            $selectedSkills = $skills->random($numSkills);
            
            foreach ($selectedSkills as $skill) {
                // Seleccionar nivel con distribución ponderada
                $level = $weightedLevels[array_rand($weightedLevels)];
                
                // Fecha aleatoria en los últimos 6 meses
                $randomDate = $sixMonthsAgo->copy()->addDays(rand(0, 180));
                
                // Asignar skill al usuario con nivel y fecha
                $user->skills()->attach($skill->id, [
                    'level' => $level,
                    'created_at' => $randomDate,
                    'updated_at' => $randomDate,
                ]);
                
                $totalAssignments++;
            }
        }

        $this->command->info("✓ {$totalAssignments} relaciones user-skill creadas");
        
        // 4. Estadísticas finales
        $this->command->info('');
        $this->command->info('📊 Estadísticas de la base de datos:');
        $this->command->info('   - Total de usuarios: ' . User::count());
        $this->command->info('   - Total de skills únicas: ' . Skill::count());
        $this->command->info('   - Total de asignaciones: ' . $totalAssignments);
        $this->command->info('   - Promedio de skills por usuario: ' . round($totalAssignments / User::count(), 2));
        
        // Mostrar top 5 skills más populares
        $topSkills = DB::table('skill_user')
            ->join('skills', 'skills.id', '=', 'skill_user.skill_id')
            ->select('skills.name', DB::raw('COUNT(*) as count'))
            ->groupBy('skills.name', 'skills.id')
            ->orderBy('count', 'DESC')
            ->limit(5)
            ->get();
        
        $this->command->info('');
        $this->command->info('🏆 Top 5 Skills Más Demandadas:');
        foreach ($topSkills as $index => $skill) {
            $this->command->info('   ' . ($index + 1) . '. ' . $skill->name . ' (' . $skill->count . ' usuarios)');
        }
    }
}
```

**Características del Seeder:**
- **Catálogo realista**: 30 skills técnicas actuales y demandadas
- **Distribución ponderada**: 30% Beginner, 40% Intermediate, 20% Advanced, 10% Expert
- **Datos temporales**: Distribuidos en los últimos 6 meses para análisis de tendencias
- **Cantidad realista**: 5-12 skills por usuario (similar a portfolios reales)
- **Estadísticas**: Muestra un resumen al finalizar

---

##### 2.2.8: Actualizar `DatabaseSeeder`

Edita `database/seeders/DatabaseSeeder.php` para incluir el nuevo seeder:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Orden importante: primero users, luego skills
        $this->call([
            // UserSeeder::class, // Si tienes un seeder de usuarios
            SkillSeeder::class,
        ]);
    }
}
```

---

##### 2.2.9: Ejecutar el Seeder

Ejecuta el seeder para poblar la base de datos:

```bash
php artisan db:seed --class=SkillSeeder
```

O, si quieres ejecutar todos los seeders:

```bash
php artisan db:seed
```

**Salida esperada:**

```
✓ Catálogo de 30 skills creado correctamente
✓ 25 usuarios de prueba creados
✓ 215 relaciones user-skill creadas

📊 Estadísticas de la base de datos:
   - Total de usuarios: 25
   - Total de skills únicas: 30
   - Total de asignaciones: 215
   - Promedio de skills por usuario: 8.6

🏆 Top 5 Skills Más Demandadas:
   1. PHP (18 usuarios)
   2. JavaScript (17 usuarios)
   3. Laravel (16 usuarios)
   4. MySQL (15 usuarios)
   5. Git (14 usuarios)
```

##### ✅ Verificación Final del Paso 2.2

Antes de continuar con el resto de la práctica, verifica que:

- [ ] Las migraciones se ejecutaron correctamente: `php artisan migrate:status`
- [ ] El seeder pobló los datos: Verifica con `php artisan tinker` que `App\Models\Skill::count()` retorna 30
- [ ] Los usuarios tienen skills asignadas: `App\Models\User::first()->skills->count()` retorna un número entre 5-12
- [ ] Las relaciones funcionan correctamente: `App\Models\Skill::first()->users` retorna una colección de usuarios
- [ ] Los niveles están distribuidos: Consulta `Illuminate\Support\Facades\DB::table('skill_user')->groupBy('level')->selectRaw('level, count(*) as total')->get()`

**Prueba rápida en Tinker:**

```bash
php artisan tinker
```

```php
// Verificar estructura
App\Models\Skill::count(); // Debe ser 30
App\Models\User::count(); // Debe ser >= 25
Illuminate\Support\Facades\DB::table('skill_user')->count(); // Debe ser aprox. 200-300

// Ver un usuario con sus skills y niveles
$user = App\Models\User::first();
$user->skills->map(function($skill) {
    return $skill->name . ' (' . $skill->pivot->level . ')';
});

// Ver la skill más popular
$topSkill = App\Models\Skill::withCount('users')->orderBy('users_count', 'desc')->first();
echo "{$topSkill->name} tiene {$topSkill->users_count} usuarios";

// Salir
exit
```

Si todas las verificaciones pasan, ¡estás listo para continuar con el análisis de datos! 🎉

---

** Nota Pedagógica:**

Esta estructura de base de datos demuestra:
- **Relaciones N:M** correctamente implementadas
- **Tabla pivot** con información adicional (level)
- **Normalización** (skills como entidad separada evita duplicados)
- **Índices** para optimizar consultas de análisis
- **Seeds realistas** para pruebas significativas
- **Factory pattern** para testing

> Los estudiantes aprenden a diseñar bases de datos escalables y eficientes para aplicaciones reales.

---

### Sesión 2.2: Crear Análisis de Competencias

#### Contexto: Análisis de Datos en ePortfolio

Vamos a crear un sistema que analice las competencias más demandadas en los portfolios de tu plataforma. Esto demuestra cómo usar análisis de datos para obtener insights de negocio.

#### Paso 2.3: Crear el Servicio de Análisis

Crea `app/Services/SkillAnalyticsService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SkillAnalyticsService
{
    /**
     * Obtener las habilidades más demandadas
     * 
     * @param int $limit
     * @return array
     */
    public function getMostDemandedSkills(int $limit = 10): array
    {
        $skills = DB::table('skill_user')
            ->join('skills', 'skills.id', '=', 'skill_user.skill_id')
            ->select('skills.name', DB::raw('COUNT(*) as count'))
            ->groupBy('skills.name', 'skills.id')
            ->orderBy('count', 'DESC')
            ->limit($limit)
            ->get();
        
        return [
            'labels' => $skills->pluck('name')->toArray(),
            'values' => $skills->pluck('count')->toArray(),
        ];
    }
    
    /**
     * Obtener distribución de niveles de competencia
     * 
     * @param string $skillName
     * @return array
     */
    public function getSkillLevelDistribution(string $skillName): array
    {
        $distribution = DB::table('skill_user')
            ->join('skills', 'skills.id', '=', 'skill_user.skill_id')
            ->where('skills.name', $skillName)
            ->select('skill_user.level', DB::raw('COUNT(*) as count'))
            ->groupBy('skill_user.level')
            ->get();
        
        return [
            'labels' => $distribution->pluck('level')->toArray(),
            'values' => $distribution->pluck('count')->toArray(),
        ];
    }
    
    /**
     * Obtener tendencias de habilidades por mes
     * 
     * @param int $months
     * @return array
     */
    public function getSkillTrends(int $months = 6): array
    {
        $trends = DB::table('skill_user')
            ->join('skills', 'skills.id', '=', 'skill_user.skill_id')
            ->select(
                DB::raw('DATE_FORMAT(skill_user.created_at, "%Y-%m") as month'),
                'skills.name',
                DB::raw('COUNT(*) as count')
            )
            ->where('skill_user.created_at', '>=', now()->subMonths($months))
            ->groupBy('month', 'skills.name', 'skills.id')
            ->orderBy('month')
            ->get();
        
        // Agrupar por habilidad
        $groupedBySkill = $trends->groupBy('name');
        
        $result = [];
        foreach ($groupedBySkill as $skill => $data) {
            $result[$skill] = [
                'labels' => $data->pluck('month')->toArray(),
                'values' => $data->pluck('count')->toArray(),
            ];
        }
        
        return $result;
    }
    
    /**
     * Obtener estadísticas generales
     * 
     * @return array
     */
    public function getGeneralStats(): array
    {
        $totalSkillAssignments = DB::table('skill_user')->count();
        $uniqueSkills = DB::table('skills')->count();
        $totalUsers = DB::table('users')->count();
        $avgSkillsPerUser = $totalUsers > 0 ? round($totalSkillAssignments / $totalUsers, 2) : 0;
        
        return [
            'total_skills' => $totalSkillAssignments,
            'unique_skills' => $uniqueSkills,
            'avg_per_portfolio' => $avgSkillsPerUser,
        ];
    }
    
    /**
     * Obtener habilidades relacionadas
     * 
     * @param string $skillName
     * @param int $limit
     * @return array
     */
    public function getRelatedSkills(string $skillName, int $limit = 5): array
    {
        // Encuentra el ID de la skill especificada
        $skill = DB::table('skills')->where('name', $skillName)->first();
        
        if (!$skill) {
            return [
                'skill' => $skillName,
                'related' => [],
                'counts' => [],
            ];
        }
        
        // Encuentra usuarios que tienen la habilidad especificada
        $usersWithSkill = DB::table('skill_user')
            ->where('skill_id', $skill->id)
            ->pluck('user_id');
        
        // Encuentra otras habilidades en esos usuarios
        $relatedSkills = DB::table('skill_user')
            ->join('skills', 'skills.id', '=', 'skill_user.skill_id')
            ->whereIn('skill_user.user_id', $usersWithSkill)
            ->where('skill_user.skill_id', '!=', $skill->id)
            ->select('skills.name', DB::raw('COUNT(*) as count'))
            ->groupBy('skills.name', 'skills.id')
            ->orderBy('count', 'DESC')
            ->limit($limit)
            ->get();
        
        return [
            'skill' => $skillName,
            'related' => $relatedSkills->pluck('name')->toArray(),
            'counts' => $relatedSkills->pluck('count')->toArray(),
        ];
    }
}
```

#### Paso 2.4: Crear el Controlador de Analytics

Crea `app/Http/Controllers/SkillAnalyticsController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\SkillAnalyticsService;
use App\Charts\MostDemandedSkills;
use App\Charts\SkillLevelDistribution;
use Illuminate\Http\Request;

class SkillAnalyticsController extends Controller
{
    protected SkillAnalyticsService $analyticsService;

    public function __construct(SkillAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Dashboard de análisis de habilidades
     */
    public function index()
    {
        // Obtener datos
        $mostDemanded = $this->analyticsService->getMostDemandedSkills(10);
        $stats = $this->analyticsService->getGeneralStats();

        // Crear gráfico de barras para habilidades más demandadas
        $chartMostDemanded = new MostDemandedSkills();
        $chartMostDemanded->title('Top 10 Habilidades Más Demandadas');
        $chartMostDemanded->labels($mostDemanded['labels']);
        $chartMostDemanded->dataset('Skills demandadas', 'bar', $mostDemanded['values']);
        $chartMostDemanded->height(300);

        // Ejemplo de habilidad específica para análisis
        $phpDistribution = $this->analyticsService->getSkillLevelDistribution('PHP');

        $chartPhpLevels = new SkillLevelDistribution();
        $chartPhpLevels->title('Distribución de Niveles - PHP');
        $chartPhpLevels->labels($phpDistribution['labels']);
        $chartPhpLevels->dataset('Distribución de Niveles', 'pie', $phpDistribution['values']);
        $chartPhpLevels->height(300);

        return view('analytics.skills', compact(
            'chartMostDemanded',
            'chartPhpLevels',
            'stats'
        ));
    }

    /**
     * API: Obtener datos de tendencias
     */
    public function trends()
    {
        $trends = $this->analyticsService->getSkillTrends(6);

        return response()->json($trends);
    }

    /**
     * API: Obtener habilidades relacionadas
     */
    public function related(Request $request)
    {
        $skill = $request->input('skill');

        if (!$skill) {
            return response()->json(['error' => 'Skill parameter required'], 400);
        }

        $related = $this->analyticsService->getRelatedSkills($skill);

        return response()->json($related);
    }
}
```

#### Paso 2.5: Crear la Vista de Analytics

Crea `resources/views/analytics/skills.blade.php`:

```blade
@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="bi bi-graph-up"></i> Análisis de Competencias
            </h2>
        </div>
    </div>

    <!-- Estadísticas Generales -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-collection"></i> Total de Habilidades
                    </h5>
                    <h2 class="mb-0">{{ number_format($stats['total_skills']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-tags"></i> Habilidades Únicas
                    </h5>
                    <h2 class="mb-0">{{ number_format($stats['unique_skills']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-person-badge"></i> Promedio por Portfolio
                    </h5>
                    <h2 class="mb-0">{{ $stats['avg_per_portfolio'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Habilidades Más Demandadas</h5>
                </div>
                <div class="card-body">
                    {!! $chartMostDemanded->container() !!}
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Distribución de Niveles</h5>
                </div>
                <div class="card-body">
                    {!! $chartPhpLevels->container() !!}
                </div>
            </div>
        </div>
    </div>

    <!-- Insights de BI -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">
                        <i class="bi bi-lightbulb"></i> Insights de Business Intelligence
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6><i class="bi bi-trending-up text-success"></i> Tendencias</h6>
                            <p class="small">
                                Las habilidades en desarrollo web (JavaScript, PHP, Laravel)
                                muestran un crecimiento del 25% en los últimos 3 meses.
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="bi bi-people text-info"></i> Perfiles</h6>
                            <p class="small">
                                Los portfolios con más de 8 habilidades diferentes tienen
                                un 40% más de visitas que la media.
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="bi bi-award text-warning"></i> Recomendaciones</h6>
                            <p class="small">
                                Se recomienda incluir habilidades en Cloud Computing y DevOps
                                para mejorar la empleabilidad.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.1/Chart.min.js" charset="utf-8"></script>
    {!! $chartMostDemanded->script() !!}
    {!! $chartPhpLevels->script() !!}

@endsection
```

#### Paso 2.6: Agregar Rutas de Analytics

En `routes/web.php`:

```php
use App\Http\Controllers\SkillAnalyticsController;

Route::middleware(['auth'])->group(function () {
    // Dashboard de analytics
    Route::get('/analytics/skills', [SkillAnalyticsController::class, 'index'])
        ->name('analytics.skills');
    
    // API endpoints
    Route::get('/api/analytics/trends', [SkillAnalyticsController::class, 'trends'])
        ->name('api.analytics.trends');
    
    Route::get('/api/analytics/related', [SkillAnalyticsController::class, 'related'])
        ->name('api.analytics.related');
});
```

#### ✅ Checkpoint Parte 2

**Prueba tu implementación:**

1. Accede a `/analytics/skills`
2. Verifica que los gráficos se renderizan correctamente
3. Comprueba que las estadísticas se calculan bien
4. Prueba las APIs de analytics con herramientas como Postman

**Criterios evaluados en esta parte:**
- ✅ **Criterio g)** Librerías de Big Data e inteligencia de negocios
- ✅ **Criterio e)** Uso de frameworks para funcionalidades específicas

---

## PARTE 3: Exportación (2 horas)

### Sesión 3.1: Exportación de Datos

#### Paso 3.1: Instalar Dependencias de Exportación

```bash
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf
```

#### Paso 3.2: Publicar Configuraciones

```bash
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider"
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

#### Paso 3.3: Crear Servicio de Exportación

Crea `app/Services/PortfolioExportService.php`:

```php
<?php

namespace App\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class PortfolioExportService
{
    /**
     * Exportar portfolio a formato JSON Resume
     *
     * @param User $portfolio
     * @return array
     */
    public function exportToJsonResume(User $portfolio): array
    {
        $user = $portfolio;

        return [
            '$schema' => 'https://raw.githubusercontent.com/jsonresume/resume-schema/v1.0.0/schema.json',
            'basics' => [
                'name' => $user->name,
                'label' => $portfolio->title ?? 'Professional',
                'email' => $user->email,
                'phone' => $portfolio->phone ?? '',
                'summary' => $portfolio->summary ?? '',
                'location' => [
                    'city' => $portfolio->city ?? '',
                    'countryCode' => $portfolio->country ?? '',
                ],
                'profiles' => $this->formatProfiles($portfolio),
            ],
            'work' => $this->formatWorkExperience($portfolio),
            'education' => $this->formatEducation($portfolio),
            'skills' => $this->formatSkills($portfolio),
            'projects' => $this->formatProjects($portfolio),
        ];
    }

    /**
     * Generar PDF del portfolio
     *
     * @param User $portfolio
     * @return \Barryvdh\DomPDF\PDF
     */
    public function exportToPdf(User $portfolio)
    {
        $data = [
            'portfolio' => $portfolio,
            'user' => $portfolio,
            'work_experience' => $portfolio->workExperience ?? [],
            'education' => $portfolio->education ?? [],
            'skills' => $portfolio->skills ?? [],
            'projects' => $portfolio->projects ?? [],
        ];

        return Pdf::loadView('exports.portfolio-pdf', $data)
            ->setPaper('a4', 'portrait');
    }

    /**
     * Formatear perfiles sociales
     */
    protected function formatProfiles(User $portfolio): array
    {
        $profiles = [];

        if ($portfolio->github_url) {
            $profiles[] = [
                'network' => 'GitHub',
                'username' => $this->extractUsername($portfolio->github_url),
                'url' => $portfolio->github_url,
            ];
        }

        if ($portfolio->linkedin_url) {
            $profiles[] = [
                'network' => 'LinkedIn',
                'url' => $portfolio->linkedin_url,
            ];
        }

        return $profiles;
    }

    /**
     * Formatear experiencia laboral
     */
    protected function formatWorkExperience(User $portfolio): array
    {
        $work = [];

        if (!$portfolio->workExperience) {
            return $work;
        }

        foreach ($portfolio->workExperience as $experience) {
            $work[] = [
                'company' => $experience->company,
                'position' => $experience->position,
                'startDate' => $experience->start_date,
                'endDate' => $experience->end_date ?? 'Present',
                'summary' => $experience->description,
            ];
        }

        return $work;
    }

    /**
     * Formatear educación
     */
    protected function formatEducation(User $portfolio): array
    {
        $education = [];

        if (!$portfolio->education) {
            return $education;
        }

        foreach ($portfolio->education as $edu) {
            $education[] = [
                'institution' => $edu->institution,
                'area' => $edu->area,
                'studyType' => $edu->study_type,
                'startDate' => $edu->start_date,
                'endDate' => $edu->end_date,
            ];
        }

        return $education;
    }

    /**
     * Formatear habilidades
     */
    protected function formatSkills(User $portfolio): array
    {
        $skills = [];

        if (!$portfolio->skills) {
            return $skills;
        }

        foreach ($portfolio->skills as $skill) {
            $skills[] = [
                'name' => $skill->name,
                'level' => $skill->level,
                'keywords' => $skill->keywords ?? [],
            ];
        }

        return $skills;
    }

    /**
     * Formatear proyectos
     */
    protected function formatProjects(User $portfolio): array
    {
        $projects = [];

        if (!$portfolio->projects) {
            return $projects;
        }

        foreach ($portfolio->projects as $project) {
            $projects[] = [
                'name' => $project->title,
                'description' => $project->description,
                'url' => $project->url,
                'keywords' => $project->technologies ?? [],
            ];
        }

        return $projects;
    }

    /**
     * Extraer nombre de usuario de URL
     */
    protected function extractUsername(string $url): string
    {
        $parts = explode('/', rtrim($url, '/'));
        return end($parts);
    }
}
```

#### Paso 3.4: Crear Controlador de Exportación

Crea `app/Http/Controllers/PortfolioExportController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PortfolioExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class PortfolioExportController extends Controller
{
    protected PortfolioExportService $exportService;

    public function __construct(PortfolioExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Exportar a JSON Resume
     */
    public function exportJson(User $portfolio)
    {
        $jsonResume = $this->exportService->exportToJsonResume($portfolio);

        $filename = 'portfolio-' . $portfolio->name . '-' . date('Y-m-d') . '.json';

        return Response::json($jsonResume, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Exportar a PDF
     */
    public function exportPdf(User $portfolio)
    {
        $pdf = $this->exportService->exportToPdf($portfolio);

        $filename = 'portfolio-' . $portfolio->name . '-' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Vista previa del PDF
     */
    public function previewPdf(User $portfolio)
    {
        $pdf = $this->exportService->exportToPdf($portfolio);

        return $pdf->stream();
    }
}
```

#### Paso 3.5: Crear Vista de PDF

Crea `resources/views/exports/portfolio-pdf.blade.php`:

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Portfolio - {{ $user->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #2c3e50;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .item {
            margin-bottom: 15px;
        }
        .item-title {
            font-weight: bold;
            color: #34495e;
        }
        .item-subtitle {
            color: #7f8c8d;
            font-style: italic;
        }
        .skills-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .skill-item {
            padding: 5px;
            background: #ecf0f1;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $user->name }}</h1>
        <p>{{ $portfolio->title ?? 'Professional Portfolio' }}</p>
        @if($user->email)
            <p>{{ $user->email }}</p>
        @endif
    </div>

    <!-- Summary -->
    @if($portfolio->summary)
    <div class="section">
        <div class="section-title">Resumen Profesional</div>
        <p>{{ $portfolio->summary }}</p>
    </div>
    @endif

    <!-- Work Experience -->
    @if(count($work_experience) > 0)
    <div class="section">
        <div class="section-title">Experiencia Laboral</div>
        @foreach($work_experience as $work)
        <div class="item">
            <div class="item-title">{{ $work->position }} - {{ $work->company }}</div>
            <div class="item-subtitle">
                {{ $work->start_date }} - {{ $work->end_date ?? 'Actualidad' }}
            </div>
            <p>{{ $work->description }}</p>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Education -->
    @if(count($education) > 0)
    <div class="section">
        <div class="section-title">Formación Académica</div>
        @foreach($education as $edu)
        <div class="item">
            <div class="item-title">{{ $edu->study_type }} - {{ $edu->area }}</div>
            <div class="item-subtitle">
                {{ $edu->institution }} ({{ $edu->start_date }} - {{ $edu->end_date }})
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Skills -->
    @if(count($skills) > 0)
    <div class="section">
        <div class="section-title">Habilidades Técnicas</div>
        <div class="skills-grid">
            @foreach($skills as $skill)
            <div class="skill-item">
                <strong>{{ $skill->name }}</strong><br>
                <small>{{ $skill->level }}</small>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Projects -->
    @if(count($projects) > 0)
    <div class="section">
        <div class="section-title">Proyectos Destacados</div>
        @foreach($projects as $project)
        <div class="item">
            <div class="item-title">{{ $project->title }}</div>
            <p>{{ $project->description }}</p>
            @if($project->url)
                <p><small>URL: {{ $project->url }}</small></p>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <div style="margin-top: 50px; text-align: center; color: #95a5a6; font-size: 10px;">
        Generado el {{ date('d/m/Y') }} desde ePortfolio
    </div>
</body>
</html>
```

#### Paso 3.6: Crear Rutas de Exportación

En `routes/web.php`:

```php
use App\Http\Controllers\PortfolioExportController;

Route::middleware(['auth'])->group(function () {
    // Exportaciones
    Route::get('/portfolio/{portfolio}/export/json', [PortfolioExportController::class, 'exportJson'])
        ->name('portfolio.export.json');
    
    Route::get('/portfolio/{portfolio}/export/pdf', [PortfolioExportController::class, 'exportPdf'])
        ->name('portfolio.export.pdf');
    
    Route::get('/portfolio/{portfolio}/export/pdf/preview', [PortfolioExportController::class, 'previewPdf'])
        ->name('portfolio.export.pdf.preview');
});
```

---


## 🏆 Conclusión

Al completar esta práctica habrás:

✅ Consumido APIs externas con Guzzle  
✅ Implementado análisis de datos con visualizaciones  
✅ Creado sistemas de importación/exportación  
✅ Desarrollado una API REST documentada
✅ Trabajado con código de terceros  
✅ Utilizado librerías de Big Data/BI

**¡Enhorabuena! Has completado una aplicación web híbrida profesional.**
