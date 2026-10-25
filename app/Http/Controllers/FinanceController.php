<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(): View
    {
        $accounts = FinancialAccount::with('transactions')->get();
        if ($accounts->isEmpty()) {
            FinancialAccount::create(['name' => 'Main Operating Cash', 'code' => '1010', 'type' => 'asset', 'balance_cents' => 5000000]);
            FinancialAccount::create(['name' => 'Accounts Receivable', 'code' => '1020', 'type' => 'asset', 'balance_cents' => 0]);
            FinancialAccount::create(['name' => 'Sales Revenue', 'code' => '4010', 'type' => 'revenue', 'balance_cents' => 0]);
            FinancialAccount::create(['name' => 'Operational Expenses', 'code' => '5010', 'type' => 'expense', 'balance_cents' => 0]);
            $accounts = FinancialAccount::all();
        }

        $transactions = FinancialTransaction::with(['account', 'creator'])->latest()->paginate(15);
        return view('finance.index', compact('accounts', 'transactions'));
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'financial_account_id' => 'required|exists:financial_accounts,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:100',
            'description' => 'required|string|max:255',
            'transaction_date' => 'required|date',
        ]);

        $amountCents = (int) round($validated['amount'] * 100);

        DB::transaction(function () use ($validated, $amountCents, $request) {
            $account = FinancialAccount::findOrFail($validated['financial_account_id']);

            FinancialTransaction::create([
                'financial_account_id' => $account->id,
                'type' => $validated['type'],
                'amount_cents' => $amountCents,
                'reference' => $validated['reference'] ?? null,
                'description' => $validated['description'],
                'transaction_date' => $validated['transaction_date'],
                'created_by' => $request->user()->id,
            ]);

            // Update account balance
            if ($validated['type'] === 'income') {
                $account->increment('balance_cents', $amountCents);
            } else {
                $account->decrement('balance_cents', $amountCents);
            }

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'FINANCE_TRANSACTION',
                'module' => 'Finance',
                'description' => "Recorded {$validated['type']} of $" . number_format($amountCents / 100, 2) . " on account {$account->name}",
            ]);
        });

        return redirect()->route('finance.index')->with('success', 'Financial transaction recorded.');
    }
}
