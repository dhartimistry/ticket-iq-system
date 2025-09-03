use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;

Route::post('/tickets', [TicketController::class, 'store']);
Route::get('/tickets', [TicketController::class, 'index']);
Route::get('/tickets/{id}', [TicketController::class, 'show']);
Route::patch('/tickets/{id}', [TicketController::class, 'update']);
Route::post('/tickets/{id}/classify', [TicketController::class, 'classify']);
Route::get('/stats', [TicketController::class, 'stats']);
