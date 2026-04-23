<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\DebitNote;
use App\Models\DebitNoteItem;
use App\Models\InventoryStock;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebitCreditNoteFixTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Client $client;
    private Supplier $supplier;
    private Warehouse $warehouse;
    private Product $product;
    private Invoice $invoice;
    private SupplierBill $bill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->actingAs($this->admin);

        // Seed required CoA accounts
        foreach ([
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset'],
            ['code' => '4100', 'name' => 'Sales Returns & Allowances', 'type' => 'revenue'],
            ['code' => '2110', 'name' => 'PPN Keluaran', 'type' => 'liability'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability'],
            ['code' => '5000', 'name' => 'Expenses', 'type' => 'expense'],
            ['code' => '1140', 'name' => 'PPN Masukan', 'type' => 'asset'],
        ] as $acct) {
            ChartOfAccount::firstOrCreate(
                ['code' => $acct['code']],
                ['name' => $acct['name'], 'type' => $acct['type'], 'is_active' => true]
            );
        }

        $this->client = Client::create([
            'code'   => 'C001',
            'name'   => 'Test Client',
            'status' => 'active',
        ]);

        $this->supplier = Supplier::create([
            'code'   => 'S001',
            'name'   => 'Test Supplier',
            'status' => 'active',
        ]);

        $this->warehouse = Warehouse::create([
            'code'      => 'WH01',
            'name'      => 'Main Warehouse',
            'is_active' => true,
        ]);

        $this->product = Product::factory()->create();

        $salesOrder = SalesOrder::create([
            'number'       => 'SO-00001',
            'client_id'    => $this->client->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date'   => now()->toDateString(),
            'subtotal'     => 1000000,
            'tax_amount'   => 0,
            'total'        => 1000000,
            'status'       => 'confirmed',
            'created_by'   => $this->admin->id,
        ]);

        $this->invoice = Invoice::create([
            'invoice_number' => 'INV-' . mt_rand(10000, 99999),
            'sales_order_id' => $salesOrder->id,
            'client_id'      => $this->client->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'subtotal'       => 1000000,
            'tax_amount'     => 0,
            'total_amount'   => 1000000,
            'paid_amount'    => 0,
            'status'         => 'unpaid',
            'created_by'     => $this->admin->id,
        ]);

        $this->bill = SupplierBill::create([
            'bill_number'  => 'BILL-' . mt_rand(10000, 99999),
            'supplier_id'  => $this->supplier->id,
            'bill_date'    => now()->toDateString(),
            'due_date'     => now()->addDays(30)->toDateString(),
            'subtotal'     => 500000,
            'tax_amount'   => 0,
            'total'        => 500000,
            'paid_amount'  => 0,
            'status'       => 'posted',
            'created_by'   => $this->admin->id,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 1 — Anti-Overclaim Validation (F-02)
    // ═══════════════════════════════════════════════════════════════

    public function test_credit_note_store_rejects_amount_exceeding_invoice_total(): void
    {
        $response = $this->post(route('accounting.credit-notes.store'), [
            'invoice_id' => $this->invoice->id,
            'date'       => now()->toDateString(),
            'amount'     => 1000001, // exceeds 1,000,000
            'tax_amount' => 0,
            'reason'     => 'Over-claimed',
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseMissing('credit_notes', ['invoice_id' => $this->invoice->id]);
    }

    public function test_credit_note_store_accepts_amount_equal_to_invoice_total(): void
    {
        $response = $this->post(route('accounting.credit-notes.store'), [
            'invoice_id' => $this->invoice->id,
            'date'       => now()->toDateString(),
            'amount'     => 1000000,
            'tax_amount' => 0,
            'reason'     => 'Full return',
        ]);

        $response->assertRedirect(route('accounting.credit-notes.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('credit_notes', [
            'invoice_id'   => $this->invoice->id,
            'total_amount' => 1000000,
            'status'       => 'draft',
        ]);
    }

    public function test_credit_note_overclaim_considers_existing_notes(): void
    {
        // Create a first CN using 600,000
        CreditNote::create([
            'credit_note_number' => 'CN-00001',
            'invoice_id'         => $this->invoice->id,
            'client_id'          => $this->client->id,
            'date'               => now(),
            'amount'             => 600000,
            'tax_amount'         => 0,
            'total_amount'       => 600000,
            'reason'             => 'Partial return',
            'status'             => 'draft',
            'created_by'         => $this->admin->id,
        ]);

        // Try to create another CN for 400,001 — should fail (only 400,000 remaining)
        $response = $this->post(route('accounting.credit-notes.store'), [
            'invoice_id' => $this->invoice->id,
            'date'       => now()->toDateString(),
            'amount'     => 400001,
            'tax_amount' => 0,
            'reason'     => 'Exceeds remaining',
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_credit_note_overclaim_ignores_cancelled_notes(): void
    {
        CreditNote::create([
            'credit_note_number' => 'CN-00001',
            'invoice_id'         => $this->invoice->id,
            'client_id'          => $this->client->id,
            'date'               => now(),
            'amount'             => 800000,
            'tax_amount'         => 0,
            'total_amount'       => 800000,
            'reason'             => 'Will be cancelled',
            'status'             => 'cancelled',
            'created_by'         => $this->admin->id,
        ]);

        // Cancelled note should not count — full 1,000,000 available
        $response = $this->post(route('accounting.credit-notes.store'), [
            'invoice_id' => $this->invoice->id,
            'date'       => now()->toDateString(),
            'amount'     => 1000000,
            'tax_amount' => 0,
            'reason'     => 'Full return after cancel',
        ]);

        $response->assertRedirect(route('accounting.credit-notes.index'));
    }

    public function test_credit_note_overclaim_includes_tax_in_total(): void
    {
        // amount + tax_amount should not exceed invoice total
        $response = $this->post(route('accounting.credit-notes.store'), [
            'invoice_id' => $this->invoice->id,
            'date'       => now()->toDateString(),
            'amount'     => 900000,
            'tax_amount' => 200000, // total 1,100,000 > 1,000,000
            'reason'     => 'Tax pushes over limit',
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_debit_note_store_rejects_amount_exceeding_bill_total(): void
    {
        $response = $this->post(route('accounting.debit-notes.store'), [
            'supplier_bill_id' => $this->bill->id,
            'date'             => now()->toDateString(),
            'amount'           => 500001, // exceeds 500,000
            'tax_amount'       => 0,
            'reason'           => 'Over-claimed',
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseMissing('debit_notes', ['supplier_bill_id' => $this->bill->id]);
    }

    public function test_debit_note_store_accepts_amount_equal_to_bill_total(): void
    {
        $response = $this->post(route('accounting.debit-notes.store'), [
            'supplier_bill_id' => $this->bill->id,
            'date'             => now()->toDateString(),
            'amount'           => 500000,
            'tax_amount'       => 0,
            'reason'           => 'Full return',
        ]);

        $response->assertRedirect(route('accounting.debit-notes.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('debit_notes', [
            'supplier_bill_id' => $this->bill->id,
            'total_amount'     => 500000,
            'status'           => 'draft',
        ]);
    }

    public function test_debit_note_overclaim_considers_existing_notes(): void
    {
        DebitNote::create([
            'debit_note_number' => 'DN-00001',
            'supplier_bill_id'  => $this->bill->id,
            'supplier_id'       => $this->supplier->id,
            'date'              => now(),
            'amount'            => 400000,
            'tax_amount'        => 0,
            'total_amount'      => 400000,
            'reason'            => 'Partial return',
            'status'            => 'approved',
            'created_by'        => $this->admin->id,
        ]);

        // Only 100,000 remaining — try 100,001
        $response = $this->post(route('accounting.debit-notes.store'), [
            'supplier_bill_id' => $this->bill->id,
            'date'             => now()->toDateString(),
            'amount'           => 100001,
            'tax_amount'       => 0,
            'reason'           => 'Exceeds remaining',
        ]);

        $response->assertSessionHasErrors('amount');
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 2 — Line Items & Warehouse (F-08, F-09)
    // ═══════════════════════════════════════════════════════════════

    public function test_credit_note_store_creates_line_items(): void
    {
        $response = $this->post(route('accounting.credit-notes.store'), [
            'invoice_id'   => $this->invoice->id,
            'warehouse_id' => $this->warehouse->id,
            'date'         => now()->toDateString(),
            'amount'       => 200000,
            'tax_amount'   => 0,
            'reason'       => 'Goods returned',
            'items'        => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 20000],
                ['product_id' => $this->product->id, 'quantity' => 3, 'unit_price' => 10000],
            ],
        ]);

        $response->assertRedirect(route('accounting.credit-notes.index'));

        $cn = CreditNote::where('invoice_id', $this->invoice->id)->first();
        $this->assertNotNull($cn);
        $this->assertEquals($this->warehouse->id, $cn->warehouse_id);
        $this->assertCount(2, $cn->items);

        $first = $cn->items->first();
        $this->assertEquals(5, $first->quantity);
        $this->assertEquals(20000, $first->unit_price);
        $this->assertEquals(100000, $first->subtotal); // 5 * 20000
    }

    public function test_debit_note_store_creates_line_items(): void
    {
        $response = $this->post(route('accounting.debit-notes.store'), [
            'supplier_bill_id' => $this->bill->id,
            'warehouse_id'     => $this->warehouse->id,
            'date'             => now()->toDateString(),
            'amount'           => 100000,
            'tax_amount'       => 0,
            'reason'           => 'Supplier defect',
            'items'            => [
                ['product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 5000],
            ],
        ]);

        $response->assertRedirect(route('accounting.debit-notes.index'));

        $dn = DebitNote::where('supplier_bill_id', $this->bill->id)->first();
        $this->assertNotNull($dn);
        $this->assertEquals($this->warehouse->id, $dn->warehouse_id);
        $this->assertCount(1, $dn->items);
        $this->assertEquals(50000, $dn->items->first()->subtotal); // 10 * 5000
    }

    public function test_credit_note_store_works_without_items(): void
    {
        $response = $this->post(route('accounting.credit-notes.store'), [
            'invoice_id' => $this->invoice->id,
            'date'       => now()->toDateString(),
            'amount'     => 50000,
            'tax_amount' => 0,
            'reason'     => 'Price adjustment only',
        ]);

        $response->assertRedirect(route('accounting.credit-notes.index'));

        $cn = CreditNote::where('invoice_id', $this->invoice->id)->first();
        $this->assertNotNull($cn);
        $this->assertNull($cn->warehouse_id);
        $this->assertCount(0, $cn->items);
    }

    public function test_credit_note_store_validates_warehouse_exists(): void
    {
        $response = $this->post(route('accounting.credit-notes.store'), [
            'invoice_id'   => $this->invoice->id,
            'warehouse_id' => 99999,
            'date'         => now()->toDateString(),
            'amount'       => 50000,
            'tax_amount'   => 0,
            'reason'       => 'Bad warehouse',
        ]);

        $response->assertSessionHasErrors('warehouse_id');
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 3 — Stock Integration (F-07)
    // ═══════════════════════════════════════════════════════════════

    public function test_credit_note_approve_creates_stock_in_movements(): void
    {
        $cn = $this->createCreditNoteWithItems();

        $response = $this->post(route('accounting.credit-notes.approve', $cn));

        $response->assertRedirect();
        $cn->refresh();
        $this->assertEquals('approved', $cn->status);

        // Stock movement should be type 'in' (customer returned goods)
        $movements = StockMovement::where('reference_type', 'credit_note')
            ->where('reference_id', $cn->id)
            ->get();
        $this->assertCount(1, $movements);
        $this->assertEquals('in', $movements->first()->type);
        $this->assertEquals(5, (float) $movements->first()->quantity);
        $this->assertEquals($this->warehouse->id, $movements->first()->warehouse_id);

        // Inventory stock should increase
        $stock = InventoryStock::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertNotNull($stock);
        $this->assertEquals(5, (float) $stock->quantity);
    }

    public function test_debit_note_approve_creates_stock_out_movements(): void
    {
        // Pre-seed stock so 'out' movement won't fail
        InventoryStock::create([
            'product_id'   => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity'     => 100,
            'reserved_quantity' => 0,
        ]);

        $dn = $this->createDebitNoteWithItems();

        $response = $this->post(route('accounting.debit-notes.approve', $dn));

        $response->assertRedirect();
        $dn->refresh();
        $this->assertEquals('approved', $dn->status);

        // Stock movement should be type 'out' (return to supplier)
        $movements = StockMovement::where('reference_type', 'debit_note')
            ->where('reference_id', $dn->id)
            ->get();
        $this->assertCount(1, $movements);
        $this->assertEquals('out', $movements->first()->type);
        $this->assertEquals(3, (float) $movements->first()->quantity);

        // Inventory stock should decrease
        $stock = InventoryStock::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEquals(97, (float) $stock->quantity); // 100 - 3
    }

    public function test_approve_without_warehouse_does_not_create_stock_movements(): void
    {
        // Create CN without warehouse
        $cn = CreditNote::create([
            'credit_note_number' => CreditNote::generateNumber(),
            'invoice_id'         => $this->invoice->id,
            'client_id'          => $this->client->id,
            'warehouse_id'       => null,
            'date'               => now(),
            'amount'             => 100000,
            'tax_amount'         => 0,
            'total_amount'       => 100000,
            'reason'             => 'Price adjustment',
            'status'             => 'draft',
            'created_by'         => $this->admin->id,
        ]);

        $this->post(route('accounting.credit-notes.approve', $cn));

        $cn->refresh();
        $this->assertEquals('approved', $cn->status);
        $this->assertDatabaseMissing('stock_movements', [
            'reference_type' => 'credit_note',
            'reference_id'   => $cn->id,
        ]);
    }

    public function test_approve_without_items_does_not_create_stock_movements(): void
    {
        // CN with warehouse but no items
        $cn = CreditNote::create([
            'credit_note_number' => CreditNote::generateNumber(),
            'invoice_id'         => $this->invoice->id,
            'client_id'          => $this->client->id,
            'warehouse_id'       => $this->warehouse->id,
            'date'               => now(),
            'amount'             => 100000,
            'tax_amount'         => 0,
            'total_amount'       => 100000,
            'reason'             => 'Price adjustment',
            'status'             => 'draft',
            'created_by'         => $this->admin->id,
        ]);

        $this->post(route('accounting.credit-notes.approve', $cn));

        $cn->refresh();
        $this->assertEquals('approved', $cn->status);
        $this->assertDatabaseMissing('stock_movements', [
            'reference_type' => 'credit_note',
            'reference_id'   => $cn->id,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // Polymorphic Journal Link (F-04)
    // ═══════════════════════════════════════════════════════════════

    public function test_credit_note_approve_creates_polymorphic_journal(): void
    {
        $cn = $this->createCreditNoteWithItems();

        $this->post(route('accounting.credit-notes.approve', $cn));

        $cn->refresh();
        $this->assertNotNull($cn->journal_entry_id);

        $journal = JournalEntry::find($cn->journal_entry_id);
        $this->assertNotNull($journal);
        $this->assertEquals(CreditNote::class, $journal->sourceable_type);
        $this->assertEquals($cn->id, $journal->sourceable_id);
    }

    public function test_debit_note_approve_creates_polymorphic_journal(): void
    {
        InventoryStock::create([
            'product_id'   => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity'     => 100,
            'reserved_quantity' => 0,
        ]);

        $dn = $this->createDebitNoteWithItems();

        $this->post(route('accounting.debit-notes.approve', $dn));

        $dn->refresh();
        $this->assertNotNull($dn->journal_entry_id);

        $journal = JournalEntry::find($dn->journal_entry_id);
        $this->assertNotNull($journal);
        $this->assertEquals(DebitNote::class, $journal->sourceable_type);
        $this->assertEquals($dn->id, $journal->sourceable_id);
    }

    // ═══════════════════════════════════════════════════════════════
    // Journal Balance (debit == credit)
    // ═══════════════════════════════════════════════════════════════

    public function test_credit_note_journal_entries_are_balanced(): void
    {
        $cn = $this->createCreditNoteWithItems();

        $this->post(route('accounting.credit-notes.approve', $cn));

        $cn->refresh();
        $journal = JournalEntry::with('items')->find($cn->journal_entry_id);

        $totalDebit  = $journal->items->sum('debit');
        $totalCredit = $journal->items->sum('credit');

        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);
        $this->assertGreaterThan(0, $totalDebit);
    }

    public function test_credit_note_journal_with_tax_is_balanced(): void
    {
        $cn = CreditNote::create([
            'credit_note_number' => CreditNote::generateNumber(),
            'invoice_id'         => $this->invoice->id,
            'client_id'          => $this->client->id,
            'warehouse_id'       => $this->warehouse->id,
            'date'               => now(),
            'amount'             => 100000,
            'tax_amount'         => 11000,
            'total_amount'       => 111000,
            'reason'             => 'Return with PPN',
            'status'             => 'draft',
            'created_by'         => $this->admin->id,
        ]);

        $this->post(route('accounting.credit-notes.approve', $cn));

        $cn->refresh();
        $journal = JournalEntry::with('items')->find($cn->journal_entry_id);

        $totalDebit  = $journal->items->sum('debit');
        $totalCredit = $journal->items->sum('credit');

        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);
        // Should have 3 items: revenue debit, ppn debit, AR credit
        $this->assertCount(3, $journal->items);
    }

    public function test_debit_note_journal_entries_are_balanced(): void
    {
        InventoryStock::create([
            'product_id'   => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity'     => 100,
            'reserved_quantity' => 0,
        ]);

        $dn = $this->createDebitNoteWithItems();

        $this->post(route('accounting.debit-notes.approve', $dn));

        $dn->refresh();
        $journal = JournalEntry::with('items')->find($dn->journal_entry_id);

        $totalDebit  = $journal->items->sum('debit');
        $totalCredit = $journal->items->sum('credit');

        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);
        $this->assertGreaterThan(0, $totalDebit);
    }

    public function test_debit_note_journal_with_tax_is_balanced(): void
    {
        $dn = DebitNote::create([
            'debit_note_number' => DebitNote::generateNumber(),
            'supplier_bill_id'  => $this->bill->id,
            'supplier_id'       => $this->supplier->id,
            'date'              => now(),
            'amount'            => 100000,
            'tax_amount'        => 11000,
            'total_amount'      => 111000,
            'reason'            => 'Return with PPN',
            'status'            => 'draft',
            'created_by'        => $this->admin->id,
        ]);

        $this->post(route('accounting.debit-notes.approve', $dn));

        $dn->refresh();
        $journal = JournalEntry::with('items')->find($dn->journal_entry_id);

        $totalDebit  = $journal->items->sum('debit');
        $totalCredit = $journal->items->sum('credit');

        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);
        // Should have 3 items: AP debit, expense credit, ppn credit
        $this->assertCount(3, $journal->items);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4 — CoA Resolution (F-03)
    // ═══════════════════════════════════════════════════════════════

    public function test_credit_note_approve_fails_if_coa_missing(): void
    {
        // Remove the required Sales Return account
        ChartOfAccount::where('code', '4100')->delete();

        $cn = CreditNote::create([
            'credit_note_number' => CreditNote::generateNumber(),
            'invoice_id'         => $this->invoice->id,
            'client_id'          => $this->client->id,
            'date'               => now(),
            'amount'             => 50000,
            'tax_amount'         => 0,
            'total_amount'       => 50000,
            'reason'             => 'Missing CoA test',
            'status'             => 'draft',
            'created_by'         => $this->admin->id,
        ]);

        $response = $this->post(route('accounting.credit-notes.approve', $cn));

        $response->assertStatus(500);
        $cn->refresh();
        $this->assertEquals('draft', $cn->status);
    }

    public function test_debit_note_approve_fails_if_coa_missing(): void
    {
        ChartOfAccount::where('code', '2000')->delete();

        $dn = DebitNote::create([
            'debit_note_number' => DebitNote::generateNumber(),
            'supplier_bill_id'  => $this->bill->id,
            'supplier_id'       => $this->supplier->id,
            'date'              => now(),
            'amount'            => 50000,
            'tax_amount'        => 0,
            'total_amount'      => 50000,
            'reason'            => 'Missing CoA test',
            'status'            => 'draft',
            'created_by'        => $this->admin->id,
        ]);

        $response = $this->post(route('accounting.debit-notes.approve', $dn));

        $response->assertStatus(500);
        $dn->refresh();
        $this->assertEquals('draft', $dn->status);
    }

    // ═══════════════════════════════════════════════════════════════
    // Already Approved Guard
    // ═══════════════════════════════════════════════════════════════

    public function test_credit_note_cannot_be_approved_twice(): void
    {
        $cn = $this->createCreditNoteWithItems();

        // First approval
        $this->post(route('accounting.credit-notes.approve', $cn));
        $cn->refresh();
        $this->assertEquals('approved', $cn->status);

        // Second approval — should be blocked
        $response = $this->post(route('accounting.credit-notes.approve', $cn));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_debit_note_cannot_be_approved_twice(): void
    {
        InventoryStock::create([
            'product_id'   => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity'     => 100,
            'reserved_quantity' => 0,
        ]);

        $dn = $this->createDebitNoteWithItems();

        $this->post(route('accounting.debit-notes.approve', $dn));
        $dn->refresh();
        $this->assertEquals('approved', $dn->status);

        $response = $this->post(route('accounting.debit-notes.approve', $dn));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ═══════════════════════════════════════════════════════════════
    // Model Relationships
    // ═══════════════════════════════════════════════════════════════

    public function test_credit_note_model_has_items_relationship(): void
    {
        $cn = CreditNote::create([
            'credit_note_number' => CreditNote::generateNumber(),
            'invoice_id'         => $this->invoice->id,
            'client_id'          => $this->client->id,
            'warehouse_id'       => $this->warehouse->id,
            'date'               => now(),
            'amount'             => 50000,
            'tax_amount'         => 0,
            'total_amount'       => 50000,
            'reason'             => 'Test',
            'status'             => 'draft',
            'created_by'         => $this->admin->id,
        ]);

        $cn->items()->create([
            'product_id' => $this->product->id,
            'quantity'   => 2,
            'unit_price' => 25000,
            'subtotal'   => 50000,
        ]);

        $fresh = CreditNote::with('items')->find($cn->id);
        $this->assertCount(1, $fresh->items);
        $this->assertInstanceOf(CreditNoteItem::class, $fresh->items->first());
    }

    public function test_debit_note_model_has_items_relationship(): void
    {
        $dn = DebitNote::create([
            'debit_note_number' => DebitNote::generateNumber(),
            'supplier_bill_id'  => $this->bill->id,
            'supplier_id'       => $this->supplier->id,
            'warehouse_id'      => $this->warehouse->id,
            'date'              => now(),
            'amount'            => 50000,
            'tax_amount'        => 0,
            'total_amount'      => 50000,
            'reason'            => 'Test',
            'status'            => 'draft',
            'created_by'        => $this->admin->id,
        ]);

        $dn->items()->create([
            'product_id' => $this->product->id,
            'quantity'   => 2,
            'unit_price' => 25000,
            'subtotal'   => 50000,
        ]);

        $fresh = DebitNote::with('items')->find($dn->id);
        $this->assertCount(1, $fresh->items);
        $this->assertInstanceOf(DebitNoteItem::class, $fresh->items->first());
    }

    public function test_credit_note_model_has_warehouse_relationship(): void
    {
        $cn = CreditNote::create([
            'credit_note_number' => CreditNote::generateNumber(),
            'invoice_id'         => $this->invoice->id,
            'client_id'          => $this->client->id,
            'warehouse_id'       => $this->warehouse->id,
            'date'               => now(),
            'amount'             => 50000,
            'tax_amount'         => 0,
            'total_amount'       => 50000,
            'reason'             => 'Test',
            'status'             => 'draft',
            'created_by'         => $this->admin->id,
        ]);

        $this->assertNotNull($cn->warehouse);
        $this->assertEquals($this->warehouse->id, $cn->warehouse->id);
    }

    public function test_invoice_has_credit_notes_relationship(): void
    {
        CreditNote::create([
            'credit_note_number' => CreditNote::generateNumber(),
            'invoice_id'         => $this->invoice->id,
            'client_id'          => $this->client->id,
            'date'               => now(),
            'amount'             => 50000,
            'tax_amount'         => 0,
            'total_amount'       => 50000,
            'reason'             => 'Test',
            'status'             => 'draft',
            'created_by'         => $this->admin->id,
        ]);

        $invoice = Invoice::with('creditNotes')->find($this->invoice->id);
        $this->assertCount(1, $invoice->creditNotes);
    }

    public function test_supplier_bill_has_debit_notes_relationship(): void
    {
        DebitNote::create([
            'debit_note_number' => DebitNote::generateNumber(),
            'supplier_bill_id'  => $this->bill->id,
            'supplier_id'       => $this->supplier->id,
            'date'              => now(),
            'amount'            => 50000,
            'tax_amount'        => 0,
            'total_amount'      => 50000,
            'reason'            => 'Test',
            'status'            => 'draft',
            'created_by'        => $this->admin->id,
        ]);

        $bill = SupplierBill::with('debitNotes')->find($this->bill->id);
        $this->assertCount(1, $bill->debitNotes);
    }

    // ═══════════════════════════════════════════════════════════════
    // View / Index Tests
    // ═══════════════════════════════════════════════════════════════

    public function test_credit_note_index_loads(): void
    {
        $response = $this->get(route('accounting.credit-notes.index'));
        $response->assertOk();
    }

    public function test_credit_note_create_passes_required_data(): void
    {
        $response = $this->get(route('accounting.credit-notes.create'));
        $response->assertOk();
        $response->assertViewHasAll(['invoices', 'clients', 'products', 'warehouses']);
    }

    public function test_debit_note_index_loads(): void
    {
        $response = $this->get(route('accounting.debit-notes.index'));
        $response->assertOk();
    }

    public function test_debit_note_create_passes_required_data(): void
    {
        $response = $this->get(route('accounting.debit-notes.create'));
        $response->assertOk();
        $response->assertViewHasAll(['bills', 'suppliers', 'products', 'warehouses']);
    }

    // ═══════════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════════

    private function createCreditNoteWithItems(): CreditNote
    {
        $cn = CreditNote::create([
            'credit_note_number' => CreditNote::generateNumber(),
            'invoice_id'         => $this->invoice->id,
            'client_id'          => $this->client->id,
            'warehouse_id'       => $this->warehouse->id,
            'date'               => now(),
            'amount'             => 100000,
            'tax_amount'         => 0,
            'total_amount'       => 100000,
            'reason'             => 'Customer return',
            'status'             => 'draft',
            'created_by'         => $this->admin->id,
        ]);

        $cn->items()->create([
            'product_id' => $this->product->id,
            'quantity'   => 5,
            'unit_price' => 20000,
            'subtotal'   => 100000,
        ]);

        return $cn;
    }

    private function createDebitNoteWithItems(): DebitNote
    {
        $dn = DebitNote::create([
            'debit_note_number' => DebitNote::generateNumber(),
            'supplier_bill_id'  => $this->bill->id,
            'supplier_id'       => $this->supplier->id,
            'warehouse_id'      => $this->warehouse->id,
            'date'              => now(),
            'amount'            => 60000,
            'tax_amount'        => 0,
            'total_amount'      => 60000,
            'reason'            => 'Supplier defect return',
            'status'            => 'draft',
            'created_by'        => $this->admin->id,
        ]);

        $dn->items()->create([
            'product_id' => $this->product->id,
            'quantity'   => 3,
            'unit_price' => 20000,
            'subtotal'   => 60000,
        ]);

        return $dn;
    }
}
