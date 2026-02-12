<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
use App\Services\Epicor\Estu\EstuBuilder;


Route::any('/order', function (Request $request) {

    $data = [
        'method'  => $request->method(),
        'url'     => $request->fullUrl(),
        'ip'      => $request->ip(),
        'headers' => $request->headers->all(),
        'query'   => $request->query(),
        'body'    => $request->getContent(),
        'json'    => $request->all(),
    ];

    $filename = now()->format('Y-m-d_H-i-s')
        . '_' . Str::uuid() . '.json';

    Storage::disk('local')->put(
        'woo-orders/' . $filename,
        json_encode($data, JSON_PRETTY_PRINT)
    );

    return response()->json([
        'status' => 'ok',
        'method' => $request->method(),
    ], 200);
});



Route::get('/', function () {



    $builder = app(EstuBuilder::class);



    $file = $builder->reset()

        // ================= HEADER (H) =================
        ->addHeader([
            'transaction_date' => '01282026',
            'transaction_time' => '143215',
            'customer' => 458921,
            'customer_number' => 99,
            'transaction_total' => 346.75,
            'total_sales_tax' => 18.32,
            'third_party_vendor_code' => 1,
            'ship_to_name' => 'John Carter',
            'ship_to_address_1' => '742 Evergreen Terrace',
            'ship_to_address_2' => 'Suite 12',
            'ship_to_address_3' => 'Springfield IL 62704',
            'store_number' => 1,
            'job_number' => 88,
            'tax_code' => 88,
            'sales_tax_rate' => 99,
            'transaction_number' => '458992',
            'direct_ship' => 'Y',

        ])

        // ================= DETAIL 1 (D) =================
        ->addDetail([
            'sku'         => 'HUSQ-300BL',
            'taxable'     => 'Y',
            'quantity'         => 2,
            'unit_price' => 129.995,
            'extended_price'   => 259.99,
            'filler'=>'   '
        ])

//        // ================= DETAIL 2 (D) =================
//        ->addDetail([
//            'sku'         => 'CHAIN-18IN',
//            'taxable'     => 'Y',
//            'qty'         => 1,
//            'unit_price' => 86.76,
//            'extended'   => 86.76,
//        ])

        ->save();

    echo str_replace("\n", "</br>", $file);

//    $file = $builder->reset()
//        ->addHeader([...])
//        ->addDetail([...])
//        ->build();

});



use App\Services\Epicor\Estu\EstuParser;
Route::get('/parser', function () {

    // $estu1 = Storage::disk('public')->get('ORDER_213612.ESTU');
    $estu2 = Storage::disk('public')->get('ORDER_224299.DAT');
    $estu3 = Storage::disk('public')->get('224299_6701e52f-09f8-466d-a10f-6e70a35c31f8.temp');


// parser din container
    $parser = app(EstuParser::class);

// parse
    // $data1 = $parser->parse($estu1);
    $data2 = $parser->parse($estu2);
    $data3 = $parser->parse($estu3);

// vezi rezultatele
    echo '<pre>';
    print_r( $data2 );

    print_r($data3);
    echo '</pre>';
});

use Illuminate\Support\Str;
Route::any('/csv', function (Request $request) {

    $data = [
        'method'  => $request->method(),
        'url'     => $request->fullUrl(),
        'ip'      => $request->ip(),
        'headers' => $request->headers->all(),
        'query'   => $request->query(),
        'body'    => $request->getContent(),
        'json'    => $request->all(),
    ];

    $filename = now()->format('Y-m-d_H-i-s')
        . '_' . Str::uuid() . '.json';

    Storage::disk('local')->put(
        'woo-orders/' . $filename,
        json_encode($data, JSON_PRETTY_PRINT)
    );

    return response()->json([
        'status' => 'ok',
        'method' => $request->method(),
    ], 200);

});

function parseFormat(string $format): array
{
    $format = trim($format);

    // -----------------------------
    // TEXT: x(n) / X(n)
    // -----------------------------
    if (preg_match('/^[xX]\((\d+)\)$/', $format, $m)) {
        return [
            'type'   => 'text',
            'length' => (int) $m[1],
        ];
    }

    // -----------------------------
    // SIGNED NUMERIC: 9(n)v9(m)±
    // -----------------------------
    if (preg_match('/^9\((\d+)\)v9\((\d+)\)±$/', $format, $m)) {
        return [
            'type' => 'signed',
            'int'  => (int) $m[1],
            'dec'  => (int) $m[2],
        ];
    }

    // -----------------------------
    // NUMERIC cu zecimale: 9(n)v9(m)
    // -----------------------------
    if (preg_match('/^9\((\d+)\)v9\((\d+)\)$/', $format, $m)) {
        return [
            'type'   => 'numeric',
            'length' => (int) $m[1] + (int) $m[2],
            'dec'    => (int) $m[2],
        ];
    }

    // -----------------------------
    // NUMERIC întreg: 9(n)
    // -----------------------------
    if (preg_match('/^9\((\d+)\)$/', $format, $m)) {
        return [
            'type'   => 'numeric',
            'length' => (int) $m[1],
            'dec'    => 0,
        ];
    }

    // -----------------------------
    // DECIMAL pur: v9(n)
    // -----------------------------
    if (preg_match('/^v9\((\d+)\)$/', $format, $m)) {
        return [
            'type'   => 'numeric',
            'length' => (int) $m[1],
            'dec'    => (int) $m[1],
        ];
    }

    // -----------------------------
    // FALLBACK
    // -----------------------------
    return [
        'type' => 'unknown',
    ];
}




Route::get('/test', function () {

    $data=   [

        /* -------------------------------------------------
         Start 1 End 1
         Record ID – Header record identifier
        ------------------------------------------------- */
        'record_id' => [
            'no' => 1,
            'size' => 1,
            'format' => 'x(01)',
            'required' => 'R',
            'comments' => 'Always "H".',
            'positions' => '1-1'
        ],

        /* -------------------------------------------------
         Start 2 End 9
         Transaction Date – MMDDYYYY
        ------------------------------------------------- */
        'transaction_date' => [
            'no' => 2,
            'size' => 8,
            'format' => '9(08)',
            'required' =>'R',
            'comments' => 'Transaction date in MMDDYYYY format.',
            'positions' => '2-9'
        ],

        /* -------------------------------------------------
         Start 10 End 15
         Transaction Time – HHMMSS
        ------------------------------------------------- */
        'transaction_time' => [
            'no' => 3,
            'size' => 6,
            'format' => '9(06)',
            'required' => 'D',
            'comments' => 'Transaction time in HHMMSS format.',
            'positions' => '10-15'
        ],

        /* -------------------------------------------------
         Start 16 End 16
         Store Number – Posting store
        ------------------------------------------------- */
        'store_number' => [
            'no' => 4,
            'size' => 1,
            'format' => 'x(01)',
            'required' => 'D',
            'comments' => 'Store number where transaction posts.',
            'positions' => '16-16'
        ],

        /* -------------------------------------------------
         Start 17 End 22
         Customer Number – MCR
        ------------------------------------------------- */
        'customer_number' => [
            'no' => 5,
            'size' => 6,
            'format' => '9(06)',
            'required' =>'R',
            'comments' => 'Eagle MCR customer number.',
            'positions' => '17-22'
        ],

        /* -------------------------------------------------
         Start 23 End 25
         Job Number – Customer job
        ------------------------------------------------- */
        'job_number' => [
            'no' => 6,
            'size' => 3,
            'format' => '9(03)',
            'required' => 'D',
            'comments' => 'Customer job number.',
            'positions' => '23-25'
        ],

        /* -------------------------------------------------
         Start 26 End 28
         Tax Code – MTX
        ------------------------------------------------- */
        'tax_code' => [
            'no' => 7,
            'size' => 3,
            'format' => 'x(03)',
            'required' => 'D',
            'comments' => 'Eagle MTX tax code.',
            'positions' => '26-28'
        ],

        /* -------------------------------------------------
         Start 29 End 33
         Sales Tax Rate
        ------------------------------------------------- */
        'sales_tax_rate' => [
            'no' => 8,
            'size' => 5,
            'format' => 'v9(5)',
            'required' => 'D',
            'comments' => 'Sales tax rate charged.',
            'positions' => '29-33'
        ],

        /* -------------------------------------------------
         Start 34 End 34
         Pricing Indicator
        ------------------------------------------------- */
        'pricing_indicator' => [
            'no' => 9,
            'size' => 1,
            'format' => 'x(01)',
            'required' => 'D',
            'comments' => 'Pricing method indicator.',
            'positions' => '34-34'
        ],

        /* -------------------------------------------------
         Start 35 End 38
         Pricing Percent
        ------------------------------------------------- */
        'pricing_percent' => [
            'no' => 10,
            'size' => 4,
            'format' => 'v9(4)',
            'required' => 'Z',
            'comments' => 'Percentage for pricing indicator.',
            'positions' => '35-38'
        ],

        /* -------------------------------------------------
         Start 39 End 48
         Clerk ID
        ------------------------------------------------- */
        'clerk' => [
            'no' => 11,
            'size' => 10,
            'format' => 'x(10)',
            'required' => 'D',
            'comments' => 'POS clerk identifier.',
            'positions' => '39-48'
        ],

        /* -------------------------------------------------
         Start 49 End 60
         Purchase Order Number
        ------------------------------------------------- */
        'purchase_order_number' => [
            'no' => 12,
            'size' => 12,
            'format' => 'x(12)',
            'required' => 'B',
            'comments' => 'Customer purchase order number.',
            'positions' => '49-60'
        ],

        /* -------------------------------------------------
         Start 61 End 70
         Transaction Total
        ------------------------------------------------- */
        'transaction_total' => [
            'no' => 13,
            'size' => 10,
            'format' => '9(7)v9(2)±',
            'required' =>'R',
            'comments' => 'Total transaction amount including tax.',
            'positions' => '61-70'
        ],

        /* -------------------------------------------------
         Start 71 End 71
         Sale Taxable
        ------------------------------------------------- */
        'sale_taxable' => [
            'no' => 14,
            'size' => 1,
            'format' => 'x(01)',
            'required' => 'E',
            'comments' => 'Y = taxable, N = non-taxable.',
            'positions' => '71-71'
        ],

        /* -------------------------------------------------
         Start 72 End 73
         Salesperson Number
        ------------------------------------------------- */
        'salesperson_number' => [
            'no' => 15,
            'size' => 2, 'format' => 'x(02)', 'required' => 'D',
            'comments' => 'Salesperson code.', 'positions' => '72-73'
        ],

        /* -------------------------------------------------
         Start 74 End 83
         Total Sales Tax
        ------------------------------------------------- */
        'total_sales_tax' => [
            'no' => 16, 'size' => 10, 'format' => '9(7)v9(2)±', 'required' =>'R',
            'comments' => 'Total sales tax amount.', 'positions' => '74-83'
        ],

        /* -------------------------------------------------
         Start 84 End 93
         Unused
        ------------------------------------------------- */
        'unused' => [
            'no' => 17, 'size' => 10, 'format' => 'x(10)', 'required' => 'B',
            'comments' => 'Always blank.', 'positions' => '84-93'
        ],

        /* -------------------------------------------------
         Start 94 End 123
         Instructions 1
        ------------------------------------------------- */
        'instructions_1' => [
            'no' => 18, 'size' => 30, 'format' => 'x(30)', 'required' => 'B',
            'comments' => 'Special instructions text.', 'positions' => '94-123'
        ],

        /* -------------------------------------------------
         Start 124 End 153
         Instructions 2
        ------------------------------------------------- */
        'instructions_2' => [
            'no' => 19, 'size' => 30, 'format' => 'x(30)', 'required' => 'B',
            'comments' => 'Additional instructions.', 'positions' => '124-153'
        ],

        /* -------------------------------------------------
         Start 154 End 183
         Ship To Name
        ------------------------------------------------- */
        'ship_to_name' => [
            'no' => 20, 'size' => 30, 'format' => 'x(30)', 'required' => 'B',
            'comments' => 'Ship-to name.', 'positions' => '154-183'
        ],

        /* -------------------------------------------------
         Start 184 End 213
         Ship To Address 1
        ------------------------------------------------- */
        'ship_to_address_1' => [
            'no' => 21, 'size' => 30, 'format' => 'x(30)', 'required' => 'B',
            'comments' => 'Ship-to street line 1.', 'positions' => '184-213'
        ],

        /* -------------------------------------------------
         Start 214 End 243
         Ship To Address 2
        ------------------------------------------------- */
        'ship_to_address_2' => [
            'no' => 22, 'size' => 30, 'format' => 'x(30)', 'required' => 'B',
            'comments' => 'Ship-to street line 2.', 'positions' => '214-243'
        ],

        /* -------------------------------------------------
         Start 244 End 273
         Ship To Address 3
        ------------------------------------------------- */
        'ship_to_address_3' => [
            'no' => 23, 'size' => 30, 'format' => 'x(30)', 'required' => 'B',
            'comments' => 'Ship-to street line 3.', 'positions' => '244-273'
        ],

        /* -------------------------------------------------
         Start 274 End 303
         Reference Information
        ------------------------------------------------- */
        'reference_information' => [
            'no' => 24, 'size' => 30, 'format' => 'x(30)', 'required' => 'B',
            'comments' => 'POS reference / allocation reference.', 'positions' => '274-303'
        ],

        /* -------------------------------------------------
         Start 304 End 313
         Customer Telephone
        ------------------------------------------------- */
        'customer_telephone' => [
            'no' => 25, 'size' => 10, 'format' => 'x(10)', 'required' => 'D',
            'comments' => 'Customer phone number.', 'positions' => '304-313'
        ],

        /* -------------------------------------------------
         Start 314 End 332
         Customer Resale Number
        ------------------------------------------------- */
        'customer_resale_no' => [
            'no' => 26, 'size' => 19, 'format' => 'x(19)', 'required' => 'D',
            'comments' => 'Customer resale number.', 'positions' => '314-332'
        ],

        /* -------------------------------------------------
         Start 333 End 342
         Customer ID (Sort Name)
        ------------------------------------------------- */
        'customer_id' => [
            'no' => 27, 'size' => 10, 'format' => 'x(10)', 'required' => 'E',
            'comments' => 'Customer sort name.', 'positions' => '333-342'
        ],

        /* -------------------------------------------------
         Start 343 End 347
         Special Order Vendor
        ------------------------------------------------- */
        'special_order_vendor' => [
            'no' => 28, 'size' => 5, 'format' => 'x(05)', 'required' => 'B',
            'comments' => 'Special order vendor code.', 'positions' => '343-347'
        ],

        /* -------------------------------------------------
         Start 348 End 357
         Total Deposit
        ------------------------------------------------- */
        'total_deposit' => [
            'no' => 29, 'size' => 10, 'format' => '9(7)v9(2)±', 'required' => 'Z',
            'comments' => 'Deposit amount for order.', 'positions' => '348-357'
        ],

        /* -------------------------------------------------
         Start 358 End 365
         Expected Delivery Date
        ------------------------------------------------- */
        'expected_delivery_date' => [
            'no' => 30, 'size' => 8, 'format' => '9(08)', 'required' => 'D',
            'comments' => 'Expected delivery date.', 'positions' => '358-365'
        ],

        /* -------------------------------------------------
         Start 366 End 373
         Estimate Expiration Date
        ------------------------------------------------- */
        'estimate_expiration_date' => [
            'no' => 31, 'size' => 8, 'format' => '9(08)', 'required' => 'D',
            'comments' => 'Estimate expiration date.', 'positions' => '366-373'
        ],

        /* -------------------------------------------------
         Start 374 End 376
         Terminal Number
        ------------------------------------------------- */
        'terminal_number' => [
            'no' => 32, 'size' => 3, 'format' => '9(03)', 'required' => 'D',
            'comments' => 'Eagle terminal number.', 'positions' => '374-376'
        ],

        /* -------------------------------------------------
         Start 377 End 384
         Transaction Number
        ------------------------------------------------- */
        'transaction_number' => [
            'no' => 33, 'size' => 8, 'format' => 'x(08)', 'required' => 'B',
            'comments' => 'POS transaction identifier.', 'positions' => '377-384'
        ],

        /* -------------------------------------------------
         Start 385 End 385
         Transaction Type
        ------------------------------------------------- */
        'transaction_type' => [
            'no' => 34, 'size' => 1, 'format' => 'x(01)', 'required' => 'D',
            'comments' => 'Transaction type code.', 'positions' => '385-385'
        ],

        /* -------------------------------------------------
         Start 386 End 395
         Total Cash Tendered
        ------------------------------------------------- */
        'total_cash_tendered' => [
            'no' => 35, 'size' => 10, 'format' => '9(7)v9(2)±', 'required' => 'Z',
            'comments' => 'Cash tendered.', 'positions' => '386-395'
        ],

        /* -------------------------------------------------
         Start 396 End 405
         Charge Tendered
        ------------------------------------------------- */
        'charge_tendered' => [
            'no' => 36, 'size' => 10, 'format' => '9(7)v9(2)±', 'required' => 'Z',
            'comments' => 'Charge amount tendered.', 'positions' => '396-405'
        ],

        /* -------------------------------------------------
         Start 406 End 415
         Change Given
        ------------------------------------------------- */
        'change_given' => [
            'no' => 37, 'size' => 10, 'format' => '9(7)v9(2)±', 'required' => 'Z',
            'comments' => 'Change given.', 'positions' => '406-415'
        ],

        /* -------------------------------------------------
         Start 416 End 425
         Total Check Tendered
        ------------------------------------------------- */
        'total_check_tendered' => [
            'no' => 38, 'size' => 10, 'format' => '9(7)v9(2)±', 'required' => 'Z',
            'comments' => 'Check tendered amount.', 'positions' => '416-425'
        ],

        /* -------------------------------------------------
         Start 426 End 431
         Check Number
        ------------------------------------------------- */
        'check_number' => [
            'no' => 39, 'size' => 6, 'format' => '9(06)', 'required' => 'Z',
            'comments' => 'Check number if used.', 'positions' => '426-431'
        ],

        /* -------------------------------------------------
         Start 432 End 441
         Bankcard Tendered
        ------------------------------------------------- */
        'bankcard_tendered' => [
            'no' => 40, 'size' => 10, 'format' => '9(7)v9(2)±', 'required' => 'Z',
            'comments' => 'Bankcard tendered amount.', 'positions' => '432-441'
        ],

        /* -------------------------------------------------
         Start 442 End 457
         Bankcard Number
        ------------------------------------------------- */
        'bankcard_number' => [
            'no' => 41, 'size' => 16, 'format' => 'x(16)', 'required' => 'B',
            'comments' => 'Bankcard number.', 'positions' => '442-457'
        ],

        /* -------------------------------------------------
         Start 458 End 463
         Apply-To Number
        ------------------------------------------------- */
        'apply_to_number' => [
            'no' => 42, 'size' => 6, 'format' => 'x(06)', 'required' => 'B',
            'comments' => 'AR document apply-to.', 'positions' => '458-463'
        ],

        /* -------------------------------------------------
         Start 464 End 465
         Third Party Vendor Code
        ------------------------------------------------- */
        'third_party_vendor_code' => [
            'no' => 43, 'size' => 2, 'format' => 'x(02)', 'required' =>true,
            'comments' => 'Certified third-party vendor code.', 'positions' => '464-465'
        ],

        /* -------------------------------------------------
         Start 466 End 466
         Use ESTU Cost Indicator
        ------------------------------------------------- */
        'use_estu_cost_indicator' => [
            'no' => 44, 'size' => 1, 'format' => 'x(01)', 'required' => 'B',
            'comments' => 'Use ESTU cost instead of IMU.', 'positions' => '466-466'
        ],

        /* -------------------------------------------------
         Start 467 End 467
         Private Label Card Type
        ------------------------------------------------- */
        'private_label_card_type' => [
            'no' => 45, 'size' => 1, 'format' => 'x(01)', 'required' => 'B',
            'comments' => 'PLC card type.', 'positions' => '467-467'
        ],

        /* -------------------------------------------------
         Start 468 End 468
         Special Transaction Processing Flag
        ------------------------------------------------- */
        'special_transaction_processing_flag' => [
            'no' => 46, 'size' => 1, 'format' => 'x(01)', 'required' => 'B',
            'comments' => 'Special transaction processing.', 'positions' => '468-468'
        ],

        /* -------------------------------------------------
         Start 469 End 471
         Private Label Card Promo Type
        ------------------------------------------------- */
        'private_label_card_promo_type' => [
            'no' => 47, 'size' => 3, 'format' => 'x(03)', 'required' => 'B',
            'comments' => 'PLC promotion type.', 'positions' => '469-471'
        ],

        /* -------------------------------------------------
         Start 472 End 472
         TDX Transaction
        ------------------------------------------------- */
        'tdx_transaction' => [
            'no' => 48, 'size' => 1, 'format' => 'x(01)', 'required' => 'B',
            'comments' => 'TDX processing flag.', 'positions' => '472-472'
        ],

        /* -------------------------------------------------
         Start 473 End 474
         Unused
        ------------------------------------------------- */
        'unused_2' => [
            'no' => 49, 'size' => 2, 'format' => 'x(02)', 'required' => 'B',
            'comments' => 'Always spaces.', 'positions' => '473-474'
        ],

        /* -------------------------------------------------
         Start 475 End 475
         Direct Ship
        ------------------------------------------------- */
        'direct_ship' => [
            'no' => 50, 'size' => 1, 'format' => 'x(01)', 'required' => 'B',
            'comments' => 'Direct ship indicator.', 'positions' => '475-475'
        ],

        /* -------------------------------------------------
         Start 476 End 479
         Transaction Codes
        ------------------------------------------------- */
        'transaction_codes' => [
            'no' => 51, 'size' => 4, 'format' => 'x(04)', 'required' => 'B',
            'comments' => 'Transaction codes.', 'positions' => '476-479'
        ],

        /* -------------------------------------------------
         Start 480 End 480
         Ship Via Code
        ------------------------------------------------- */
        'ship_via_code' => [
            'no' => 52, 'size' => 1, 'format' => 'x(01)', 'required' => 'B',
            'comments' => 'Shipping method code.', 'positions' => '480-480'
        ],

        /* -------------------------------------------------
         Start 481 End 488
         Route Number
        ------------------------------------------------- */
        'route_number' => [
            'no' => 53, 'size' => 8, 'format' => 'x(08)', 'required' => 'B',
            'comments' => 'Route number.', 'positions' => '481-488'
        ],

        /* -------------------------------------------------
         Start 489 End 491
         Route Day
        ------------------------------------------------- */
        'route_day' => [
            'no' => 54, 'size' => 3, 'format' => 'x(03)', 'required' => 'B',
            'comments' => 'Route day (Mon–Sun).', 'positions' => '489-491'
        ],

        /* -------------------------------------------------
         Start 492 End 494
         Route Stop
        ------------------------------------------------- */
        'route_stop' => [
            'no' => 55, 'size' => 3, 'format' => 'x(03)', 'required' => 'B',
            'comments' => 'Route stop.', 'positions' => '492-494'
        ],

        /* -------------------------------------------------
         Start 495 End 495
         Delivery Time Code
        ------------------------------------------------- */
        'delivery_time_code' => [
            'no' => 56, 'size' => 1, 'format' => 'x(01)', 'required' => 'B',
            'comments' => 'Delivery time code.', 'positions' => '495-495'
        ],

        /* -------------------------------------------------
         Start 496 End 496
         Calculate Trade Discount
        ------------------------------------------------- */
        'calculate_trade_discount' => [
            'no' => 57, 'size' => 1, 'format' => 'x(01)', 'required' => 'B',
            'comments' => 'Calculate trade discount flag.', 'positions' => '496-496'
        ],

        /* -------------------------------------------------
         Start 497 End 497
         Retain Sales Tax
        ------------------------------------------------- */
        'retain_sales_tax' => [
            'no' => 58, 'size' => 1, 'format' => 'x(01)', 'required' => 'B',
            'comments' => 'Retain sales tax amount.', 'positions' => '497-497'
        ],

        /* -------------------------------------------------
         Start 498 End 507
         POS Short ID
        ------------------------------------------------- */
        'pos_short_id' => [
            'no' => 59, 'size' => 10, 'format' => 'x(10)', 'required' => 'B',
            'comments' => 'POS short identifier.', 'positions' => '498-507'
        ],

        /* -------------------------------------------------
         Start 508 End 508
         Auto Add NIF Items
        ------------------------------------------------- */
        'auto_add_nif_items' => [
            'no' => 60, 'size' => 1, 'format' => 'x(01)', 'required' => 'B',
            'comments' => 'Auto add NIF items flag.', 'positions' => '508-508'
        ],

        /* -------------------------------------------------
         Start 509 End 518
         Loyalty ID
        ------------------------------------------------- */
        'loyalty_id' => [
            'no' => 61, 'size' => 10, 'format' => 'x(10)', 'required' => 'B',
            'comments' => 'Customer loyalty ID.', 'positions' => '509-518'
        ],

    ];
    $newdata=[];

    foreach ($data as $key=>$item){

        $item['original_required']= $item['required'];
        if ($item['required'] === 'R') {
            $item['required'] = true;
        } else {
            $item['required'] = false;
        }


        $format=parseFormat($item['format']);


        $newdata[$key]=[...$item, ...$format];
    }
// EXPORT PHP
    $export = var_export($newdata, true);



// salvează fișierul
    Storage::disk('local')->put('pp.php', exportPhpConfigWithComments($newdata));
});



function extractStartEnd(?string $positions): array
{
    if (!$positions) {
        return [null, null];
    }

    if (preg_match('/(\d+)\s*-\s*(\d+)/', $positions, $m)) {
        return [(int)$m[1], (int)$m[2]];
    }

    return [null, null];
}


function buildFieldComment(string $key, array $field): string
{
    [$start, $end] = extractStartEnd($field['positions'] ?? null);

    $title = ucwords(str_replace('_', ' ', $key));
    $desc  = trim($field['comments'] ?? '');

    $out  = "/* -------------------------------------------------\n";
    if ($start !== null) {
        $out .= " * Start {$start} End {$end}\n";
    }
    $out .= " * {$title}";
    if ($desc !== '') {
        $out .= " – {$desc}";
    }
    $out .= "\n * ------------------------------------------------- */\n";

    return $out;
}


function exportPhpConfigWithComments(array $data, int $indent = 0): string
{
    $pad = str_repeat('    ', $indent);
    $out = "[\n";

    foreach ($data as $key => $value) {

        // comentariu BLOCK
        $out .= $pad . buildFieldComment($key, $value);

        // cheia
        $out .= $pad . "    '" . addslashes($key) . "' => [\n";

        foreach ($value as $k => $v) {
            $out .= $pad . "        '{$k}' => " . var_export($v, true) . ",\n";
        }

        $out .= $pad . "    ],\n\n";
    }

    $out .= $pad . "]";
    return $out;
}




