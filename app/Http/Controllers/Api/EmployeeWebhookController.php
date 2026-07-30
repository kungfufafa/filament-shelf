<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $secret = config('services.core.webhook_secret');
        $signature = $request->header('X-Signature');

        if (!$signature || hash_hmac('sha256', $request->getContent(), $secret) !== $signature) {
            abort(401, 'Invalid signature.');
        }

        $payload = $request->validate([
            'action' => 'required|string',
            'employee' => 'required|array',
            'employee.id' => 'required|integer',
            'employee.name' => 'required|string',
            'employee.email' => 'nullable|string',
            'employee.phone' => 'nullable|string',
            'employee.status' => 'required|string',
        ]);

        $action = $payload['action'];
        $empData = $payload['employee'];

        Log::info("Received employee webhook: {$action} for {$empData['id']}");

        if ($action === 'created' || $action === 'updated') {
            Employee::updateOrCreate(
                ['id' => $empData['id']],
                [
                    'name' => $empData['name'],
                    'email' => $empData['email'],
                    'phone' => $empData['phone'],
                    'status' => $empData['status'],
                ]
            );
        } elseif ($action === 'deleted') {
            Employee::where('id', $empData['id'])->delete();
        }

        return response()->json(['status' => 'success']);
    }
}
