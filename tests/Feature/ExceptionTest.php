<?php

namespace Tests\Feature;

use AnourValar\LaravelAtom\Http\Middleware\Api\Json;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExceptionTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * {@inheritDoc}
     * @see \Illuminate\Foundation\Testing\TestCase::setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Routing\Router::macro('clearAllRoutes', function () {
            $this->routes = new \Illuminate\Routing\RouteCollection();
            $this->routes->add($this->addRoute('GET', '/login', fn () => 1)->name('login'));
            $this->routes->add($this->addRoute('GET', '/verification-notice', fn () => 1)->name('verification.notice'));
            $this->routes->add($this->addRoute('GET', '/livewire-update', fn () => 1)->name('livewire.update'));
        });
        Route::clearAllRoutes();

        \Illuminate\Database\Eloquent\Model::preventLazyLoading(true);
        \Illuminate\Database\Eloquent\Model::preventAccessingMissingAttributes(true);
        \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes(true);

        config(['app.debug' => false]);
        config(['app.env' => 'production']);
        config(['logging.default' => 'null']);
        app()['env'] = 'production';

        \Date::setTestNow('2024-03-19 14:00:00');
    }

    /**
     * @return void
     */
    public function test_ThrottleRequestsException()
    {
        RateLimiter::for('test', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        Route::middleware('throttle:test')->get('/web', function () {
            return 'foo';
        });

        Route::middleware([Json::class, 'throttle:test'])->get('/api', function () {
            return 'bar';
        });

        for ($i = 0; $i < 10; $i++) {
            $this->get('/web')->assertStatus(200)->assertSee('foo');
        }

        // Web
        $this->get('/web')->assertStatus(429)->assertSee(__('Too Many Requests'));

        // Api
        $this->get('/api')->assertStatus(429)->assertExactJson([
            'message' => 'Too Many Attempts.',
            'errors' => [
                'error' => [trans('laravel_atom::exception.throttle', ['seconds' => 60])],
            ],
        ]);
    }

    /**
     * @return void
     */
    public function test_NotFoundHttpException_ModelNotFoundException()
    {
        Route::get('/web1', function () {
            return \App\User::findOrFail(0);
        });
        Route::get('/web2', function () {
            abort(404);
        });

        Route::middleware([Json::class])->get('/api1', function () {
            return \App\User::findOrFail(0);
        });
        Route::middleware([Json::class])->get('/api2', function () {
            abort(404);
        });

        // Web
        $this->get('/web1')->assertStatus(404)->assertSee(__('Not Found'));
        $this->get('/web2')->assertStatus(404)->assertSee(__('Not Found'));

        // Api
        $this->get('/api1')->assertStatus(404)->assertExactJson(['message' => 'No query results.', 'errors' => []]);
        $this->get('/api2')->assertStatus(404)->assertExactJson(['message' => 'Not Found.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_InvalidSignatureException()
    {
        Route::middleware(['signed'])->get('/web', function () {

        });

        Route::middleware([Json::class, 'signed'])->get('/api', function () {

        });

        // Web
        $this->get('/web')->assertStatus(403)->assertSee(__('Forbidden'));

        // Api
        $this->get('/api')->assertStatus(403)->assertExactJson(['message' => 'Invalid signature.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_MethodNotAllowedHttpException()
    {
        Route::get('/web', function () {

        });

        Route::get('/api', function () {

        });

        // Web
        $this->post('/web')->assertStatus(405)->assertSee(__('An Error Occurred: Method Not Allowed'));

        // Api
        $this->post('/api', [], ['Accept' => 'application/json'])->assertStatus(405)->assertExactJson([
            'message' => 'The POST method is not supported for route api. Supported methods: GET, HEAD.',
            'errors' => [],
        ]);
    }

    /**
     * @return void
     */
    public function test_AuthorizationException()
    {
        Route::middleware(['can:foo'])->get('/web1', function () {

        });
        Route::get('/web2', function () {
            \Gate::authorize('bar');
        });

        Route::middleware([Json::class, 'can:foo'])->get('/api1', function () {

        });
        Route::middleware([Json::class])->get('/api2', function () {
            \Gate::authorize('bar');
        });

        // Web
        $this->get('/web1')->assertStatus(403)->assertSee(__('Forbidden'));
        $this->get('/web2')->assertStatus(403)->assertSee(__('Forbidden'));

        // Api
        $this->get('/api1')->assertStatus(403)->assertExactJson([
            'message' => 'This action is unauthorized.',
            'errors' => [
                'error' => [__('This action is unauthorized.')],
            ],
        ]);
        $this->get('/api2')->assertStatus(403)->assertExactJson([
            'message' => 'This action is unauthorized.',
            'errors' => [
                'error' => [__('This action is unauthorized.')],
            ],
        ]);
    }

    /**
     * @return void
     */
    public function test_AuthenticationException()
    {
        //\Illuminate\Auth\Middleware\Authenticate::redirectUsing(fn () => route('login'));
        Route::middleware(['auth'])->get('/web', function () {

        });

        Route::middleware([Json::class, 'auth'])->get('/api', function () {

        });

        // Web
        $this->get('/web')->assertStatus(302)->assertRedirect(route('login'));

        // Api
        $this->get('/api')->assertStatus(401)->assertExactJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    /*public function test_HttpException_verified()
    {
        $this->actingAs(\App\User::factory()->create(['email_verified_at' => null]));

        Route::middleware(['auth', 'verified'])->get('/web', function () {

        });

        Route::middleware([Json::class, 'auth', 'verified'])->get('/api', function () {

        });

        // Web
        $this->get('/web')->assertStatus(302)->assertRedirect(route('verification.notice'));

        // Api
        $this->get('/api')->assertStatus(403)->assertExactJson([
            'message' => 'Your email address is not verified.',
            'errors' => [
                'error' => [__('Your email address is not verified.')],
            ],
        ]);
    }*/

    /**
     * @return void
     */
    public function test_TokenMismatchException()
    {
        Route::middleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])->post('/web', function () {

        });

        Route::middleware([Json::class, \Illuminate\Session\Middleware\StartSession::class, \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])->post('/api', function () {

        });

        // Web
        $this->post('/web')->assertStatus(419)->assertSee('Page Expired');

        // Api
        $this->post('/api')->assertStatus(419)->assertExactJson(['message' => 'CSRF token mismatch.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_JsonEncodingException_InvalidArgumentException_QueryException()
    {
        $closure = function () {
            $user = new \App\User();
            $user->mergeCasts(['foo' => 'json']);
            $user->foo = ['hello' => iconv('utf-8', 'cp1251', 'мир')];
        };

        Route::post('/web', $closure);
        Route::middleware([Json::class])->post('/api', $closure);

        // Web
        $this->post('/web')->assertStatus(400)->assertSee('Malformed UTF-8 characters, possibly incorrectly encoded.');

        // Api
        $this->post('/api')->assertStatus(400)->assertExactJson(['message' => 'Malformed UTF-8 characters, possibly incorrectly encoded.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_MassAssignmentException()
    {
        $closure1 = function () {
            $user = new \App\User();
            $user->fill(\Request::input());
        };
        $closure2 = function () {
            $user = new \App\User();
            $user->fields('foo')->fill(\Request::input());
        };


        Route::post('/web1', $closure1);
        Route::middleware([Json::class])->post('/api1', $closure1);

        Route::post('/web2', $closure2);
        Route::middleware([Json::class])->post('/api2', $closure2);

        // Web
        $this->post('/web1', ['foo' => 1, 'bar' => 2])->assertStatus(400)->assertSee('Add [foo] to fillable property to allow mass assignment on [App\User].');
        $this->post('/web2', ['foo' => 1, 'bar' => 2])->assertStatus(400)->assertSee('Add fillable property [bar] to allow mass assignment on [App\User]');

        // Api
        $this->post('/api1', ['foo' => 1, 'bar' => 2])->assertStatus(400)->assertExactJson([
            'message' => 'Add [foo] to fillable property to allow mass assignment on [App\User].',
            'errors' => [],
        ]);
        $this->post('/api2', ['foo' => 1, 'bar' => 2])->assertStatus(400)->assertExactJson([
            'message' => 'Add fillable property [bar] to allow mass assignment on [App\User].',
            'errors' => [],
        ]);
    }
}
