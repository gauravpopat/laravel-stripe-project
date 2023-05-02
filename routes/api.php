    <?php

    use App\Http\Controllers\AuthController;
    use App\Http\Controllers\CustomerController;
    use App\Http\Controllers\ItemController;
    use App\Http\Controllers\PaymentController;
    use App\Http\Controllers\PlanController;
    use App\Http\Controllers\PriceController;
    use App\Http\Controllers\ProductController;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;

    /*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });


    Route::middleware(['isAdmin','auth:sanctum'])->prefix('admin')->group(function(){
        Route::controller(ProductController::class)->prefix('product')->group(function () {
            Route::post('create', 'create');
        });

        Route::controller(PlanController::class)->prefix('plan')->group(function () {
            Route::post('create', 'create');
        });

        Route::controller(PriceController::class)->prefix('price')->group(function () {
            Route::post('create', 'create');
        });

        Route::controller(ItemController::class)->prefix('item')->group(function () {
            Route::post('create', 'create');
        });
    });


    Route::middleware(['isCustomer','auth:sanctum'])->prefix('customer')->group(function(){
        
        Route::controller(CustomerController::class)->group(function () {
            Route::post('update', 'update');
            Route::post('delete', 'delete');
        });

        Route::controller(PaymentController::class)->prefix('payment')->group(function () {
            Route::post('add-card', 'addCard');
            Route::post('create-intent', 'createIntent');
            Route::post('checkout-session', 'createCheckoutSession');
        });
    });

    Route::controller(AuthController::class)->group(function () {
        Route::post('register', 'register');
        Route::get('verify-email/{email_verification_code}', 'verifyEmail');
        Route::post('login', 'login');
    });
