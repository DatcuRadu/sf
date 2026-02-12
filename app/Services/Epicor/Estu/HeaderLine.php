<?php

namespace App\Services\Epicor\Estu;

class HeaderLine extends AbstractLine
{


    public function schema(): array
    {


        return [
            /* -------------------------------------------------
             * Start 1 End 1
             * Record Id – Always "H".
             * ------------------------------------------------- */
            'record_id' => [
                'no' => 1,
                'size' => 1,
                'format' => 'x(01)',
                'required' => true,
                'comments' => 'Always "H".',
                'positions' => '1-1',
                'original_required' => 'R',
                'type' => 'text',
                'length' => 1,
                'value' => 'H',
            ],

            /* -------------------------------------------------
             * Start 2 End 9
             * Transaction Date – Transaction date in MMDDYYYY format.
             * ------------------------------------------------- */
            'transaction_date' => [
                'no' => 2,
                'size' => 8,
                'format' => '9(08)',
                'required' => true,
                'comments' => 'Transaction date in MMDDYYYY format.',
                'positions' => '2-9',
                'original_required' => 'R',
                'type' => 'numeric',
                'length' => 8,
                'dec' => 0,
            ],

            /* -------------------------------------------------
             * Start 10 End 15
             * Transaction Time – Transaction time in HHMMSS format.
             * ------------------------------------------------- */
            'transaction_time' => [
                'no' => 3,
                'size' => 6,
                'format' => '9(06)',
                'required' => false,
                'comments' => 'Transaction time in HHMMSS format.',
                'positions' => '10-15',
                'original_required' => 'D',
                'type' => 'numeric',
                'length' => 6,
                'dec' => 0,
            ],

            /* -------------------------------------------------
             * Start 16 End 16
             * Store Number – Store number where transaction posts.
             * ------------------------------------------------- */
            'store_number' => [
                'no' => 4,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Store number where transaction posts.',
                'positions' => '16-16',
                'original_required' => 'D',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 17 End 22
             * Customer Number – Eagle MCR customer number.
             * ------------------------------------------------- */
            'customer_number' => [
                'no' => 5,
                'size' => 6,
                'format' => '9(06)',
                'required' => true,
                'comments' => 'Eagle MCR customer number.',
                'positions' => '17-22',
                'original_required' => 'R',
                'type' => 'numeric',
                'length' => 6,
                'dec' => 0,
            ],

            /* -------------------------------------------------
             * Start 23 End 25
             * Job Number – Customer job number.
             * ------------------------------------------------- */
            'job_number' => [
                'no' => 6,
                'size' => 3,
                'format' => '9(03)',
                'required' => false,
                'comments' => 'Customer job number.',
                'positions' => '23-25',
                'original_required' => 'D',
                'type' => 'numeric',
                'length' => 3,
                'dec' => 0,
            ],

            /* -------------------------------------------------
             * Start 26 End 28
             * Tax Code – Eagle MTX tax code.
             * ------------------------------------------------- */
            'tax_code' => [
                'no' => 7,
                'size' => 3,
                'format' => 'x(03)',
                'required' => false,
                'comments' => 'Eagle MTX tax code.',
                'positions' => '26-28',
                'original_required' => 'D',
                'type' => 'text',
                'length' => 3,
            ],

            /* -------------------------------------------------
             * Start 29 End 33
             * Sales Tax Rate – Sales tax rate charged.
             * ------------------------------------------------- */
            'sales_tax_rate' => [
                'no' => 8,
                'size' => 5,
                'format' => 'v9(5)',
                'required' => false,
                'comments' => 'Sales tax rate charged.',
                'positions' => '29-33',
                'original_required' => 'D',
                'type' => 'numeric',
                'length' => 5,
                'dec' => 5,
            ],

            /* -------------------------------------------------
             * Start 34 End 34
             * Pricing Indicator – Pricing method indicator.
             * ------------------------------------------------- */
            'pricing_indicator' => [
                'no' => 9,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Pricing method indicator.',
                'positions' => '34-34',
                'original_required' => 'D',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 35 End 38
             * Pricing Percent – Percentage for pricing indicator.
             * ------------------------------------------------- */
            'pricing_percent' => [
                'no' => 10,
                'size' => 4,
                'format' => 'v9(4)',
                'required' => false,
                'comments' => 'Percentage for pricing indicator.',
                'positions' => '35-38',
                'original_required' => 'Z',
                'type' => 'numeric',
                'length' => 4,
                'dec' => 4,
            ],

            /* -------------------------------------------------
             * Start 39 End 48
             * Clerk – POS clerk identifier.
             * ------------------------------------------------- */
            'clerk' => [
                'no' => 11,
                'size' => 10,
                'format' => 'x(10)',
                'required' => false,
                'comments' => 'POS clerk identifier.',
                'positions' => '39-48',
                'original_required' => 'D',
                'type' => 'text',
                'length' => 10,
            ],

            /* -------------------------------------------------
             * Start 49 End 60
             * Purchase Order Number – Customer purchase order number.
             * ------------------------------------------------- */
            'purchase_order_number' => [
                'no' => 12,
                'size' => 12,
                'format' => 'x(12)',
                'required' => false,
                'comments' => 'Customer purchase order number.',
                'positions' => '49-60',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 12,
            ],

            /* -------------------------------------------------
             * Start 61 End 70
             * Transaction Total – Total transaction amount including tax.
             * ------------------------------------------------- */
            'transaction_total' => [
                'no' => 13,
                'size' => 10,
                'format' => '9(7)v9(2)±',
                'required' => true,
                'comments' => 'Total transaction amount including tax.',
                'positions' => '61-70',
                'original_required' => 'R',
                'type' => 'signed',
                'int' => 7,
                'dec' => 2,
            ],

            /* -------------------------------------------------
             * Start 71 End 71
             * Sale Taxable – Y = taxable, N = non-taxable.
             * ------------------------------------------------- */
            'sale_taxable' => [
                'no' => 14,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Y = taxable, N = non-taxable.',
                'positions' => '71-71',
                'original_required' => 'E',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 72 End 73
             * Salesperson Number – Salesperson code.
             * ------------------------------------------------- */
            'salesperson_number' => [
                'no' => 15,
                'size' => 2,
                'format' => 'x(02)',
                'required' => false,
                'comments' => 'Salesperson code.',
                'positions' => '72-73',
                'original_required' => 'D',
                'type' => 'text',
                'length' => 2,
            ],

            /* -------------------------------------------------
             * Start 74 End 83
             * Total Sales Tax – Total sales tax amount.
             * ------------------------------------------------- */
            'total_sales_tax' => [
                'no' => 16,
                'size' => 10,
                'format' => '9(7)v9(2)±',
                'required' => true,
                'comments' => 'Total sales tax amount.',
                'positions' => '74-83',
                'original_required' => 'R',
                'type' => 'signed',
                'int' => 7,
                'dec' => 2,
            ],

            /* -------------------------------------------------
             * Start 84 End 93
             * Unused – Always blank.
             * ------------------------------------------------- */
            'unused' => [
                'no' => 17,
                'size' => 10,
                'format' => 'x(10)',
                'required' => false,
                'comments' => 'Always blank.',
                'positions' => '84-93',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 10,
            ],

            /* -------------------------------------------------
             * Start 94 End 123
             * Instructions 1 – Special instructions text.
             * ------------------------------------------------- */
            'instructions_1' => [
                'no' => 18,
                'size' => 30,
                'format' => 'x(30)',
                'required' => false,
                'comments' => 'Special instructions text.',
                'positions' => '94-123',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 30,
            ],

            /* -------------------------------------------------
             * Start 124 End 153
             * Instructions 2 – Additional instructions.
             * ------------------------------------------------- */
            'instructions_2' => [
                'no' => 19,
                'size' => 30,
                'format' => 'x(30)',
                'required' => false,
                'comments' => 'Additional instructions.',
                'positions' => '124-153',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 30,
            ],

            /* -------------------------------------------------
             * Start 154 End 183
             * Ship To Name – Ship-to name.
             * ------------------------------------------------- */
            'ship_to_name' => [
                'no' => 20,
                'size' => 30,
                'format' => 'x(30)',
                'required' => false,
                'comments' => 'Ship-to name.',
                'positions' => '154-183',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 30,
            ],

            /* -------------------------------------------------
             * Start 184 End 213
             * Ship To Address 1 – Ship-to street line 1.
             * ------------------------------------------------- */
            'ship_to_address_1' => [
                'no' => 21,
                'size' => 30,
                'format' => 'x(30)',
                'required' => false,
                'comments' => 'Ship-to street line 1.',
                'positions' => '184-213',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 30,
            ],

            /* -------------------------------------------------
             * Start 214 End 243
             * Ship To Address 2 – Ship-to street line 2.
             * ------------------------------------------------- */
            'ship_to_address_2' => [
                'no' => 22,
                'size' => 30,
                'format' => 'x(30)',
                'required' => false,
                'comments' => 'Ship-to street line 2.',
                'positions' => '214-243',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 30,
            ],

            /* -------------------------------------------------
             * Start 244 End 273
             * Ship To Address 3 – Ship-to street line 3.
             * ------------------------------------------------- */
            'ship_to_address_3' => [
                'no' => 23,
                'size' => 30,
                'format' => 'x(30)',
                'required' => false,
                'comments' => 'Ship-to street line 3.',
                'positions' => '244-273',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 30,
            ],

            /* -------------------------------------------------
             * Start 274 End 303
             * Reference Information – POS reference / allocation reference.
             * ------------------------------------------------- */
            'reference_information' => [
                'no' => 24,
                'size' => 30,
                'format' => 'x(30)',
                'required' => false,
                'comments' => 'POS reference / allocation reference.',
                'positions' => '274-303',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 30,
            ],

            /* -------------------------------------------------
             * Start 304 End 313
             * Customer Telephone – Customer phone number.
             * ------------------------------------------------- */
            'customer_telephone' => [
                'no' => 25,
                'size' => 10,
                'format' => 'x(10)',
                'required' => false,
                'comments' => 'Customer phone number.',
                'positions' => '304-313',
                'original_required' => 'D',
                'type' => 'text',
                'length' => 10,
            ],

            /* -------------------------------------------------
             * Start 314 End 332
             * Customer Resale No – Customer resale number.
             * ------------------------------------------------- */
            'customer_resale_no' => [
                'no' => 26,
                'size' => 19,
                'format' => 'x(19)',
                'required' => false,
                'comments' => 'Customer resale number.',
                'positions' => '314-332',
                'original_required' => 'D',
                'type' => 'text',
                'length' => 19,
            ],

            /* -------------------------------------------------
             * Start 333 End 342
             * Customer Id – Customer sort name.
             * ------------------------------------------------- */
            'customer_id' => [
                'no' => 27,
                'size' => 10,
                'format' => 'x(10)',
                'required' => false,
                'comments' => 'Customer sort name.',
                'positions' => '333-342',
                'original_required' => 'E',
                'type' => 'text',
                'length' => 10,
            ],

            /* -------------------------------------------------
             * Start 343 End 347
             * Special Order Vendor – Special order vendor code.
             * ------------------------------------------------- */
            'special_order_vendor' => [
                'no' => 28,
                'size' => 5,
                'format' => 'x(05)',
                'required' => false,
                'comments' => 'Special order vendor code.',
                'positions' => '343-347',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 5,
            ],

            /* -------------------------------------------------
             * Start 348 End 357
             * Total Deposit – Deposit amount for order.
             * ------------------------------------------------- */
            'total_deposit' => [
                'no' => 29,
                'size' => 10,
                'format' => '9(7)v9(2)±',
                'required' => false,
                'comments' => 'Deposit amount for order.',
                'positions' => '348-357',
                'original_required' => 'Z',
                'type' => 'signed',
                'int' => 7,
                'dec' => 2,
            ],

            /* -------------------------------------------------
             * Start 358 End 365
             * Expected Delivery Date – Expected delivery date.
             * ------------------------------------------------- */
            'expected_delivery_date' => [
                'no' => 30,
                'size' => 8,
                'format' => '9(08)',
                'required' => false,
                'comments' => 'Expected delivery date.',
                'positions' => '358-365',
                'original_required' => 'D',
                'type' => 'numeric',
                'length' => 8,
                'dec' => 0,
            ],

            /* -------------------------------------------------
             * Start 366 End 373
             * Estimate Expiration Date – Estimate expiration date.
             * ------------------------------------------------- */
            'estimate_expiration_date' => [
                'no' => 31,
                'size' => 8,
                'format' => '9(08)',
                'required' => false,
                'comments' => 'Estimate expiration date.',
                'positions' => '366-373',
                'original_required' => 'D',
                'type' => 'numeric',
                'length' => 8,
                'dec' => 0,
            ],

            /* -------------------------------------------------
             * Start 374 End 376
             * Terminal Number – Eagle terminal number.
             * ------------------------------------------------- */
            'terminal_number' => [
                'no' => 32,
                'size' => 3,
                'format' => '9(03)',
                'required' => false,
                'comments' => 'Eagle terminal number.',
                'positions' => '374-376',
                'original_required' => 'D',
                'type' => 'numeric',
                'length' => 3,
                'dec' => 0,
            ],

            /* -------------------------------------------------
             * Start 377 End 384
             * Transaction Number – POS transaction identifier.
             * ------------------------------------------------- */
            'transaction_number' => [
                'no' => 33,
                'size' => 8,
                'format' => 'x(08)',
                'required' => false,
                'comments' => 'POS transaction identifier.',
                'positions' => '377-384',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 8,
            ],

            /* -------------------------------------------------
             * Start 385 End 385
             * Transaction Type – Transaction type code.
             * ------------------------------------------------- */
            'transaction_type' => [
                'no' => 34,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Transaction type code.',
                'positions' => '385-385',
                'original_required' => 'D',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 386 End 395
             * Total Cash Tendered – Cash tendered.
             * ------------------------------------------------- */
            'total_cash_tendered' => [
                'no' => 35,
                'size' => 10,
                'format' => '9(7)v9(2)±',
                'required' => false,
                'comments' => 'Cash tendered.',
                'positions' => '386-395',
                'original_required' => 'Z',
                'type' => 'signed',
                'int' => 7,
                'dec' => 2,
            ],

            /* -------------------------------------------------
             * Start 396 End 405
             * Charge Tendered – Charge amount tendered.
             * ------------------------------------------------- */
            'charge_tendered' => [
                'no' => 36,
                'size' => 10,
                'format' => '9(7)v9(2)±',
                'required' => false,
                'comments' => 'Charge amount tendered.',
                'positions' => '396-405',
                'original_required' => 'Z',
                'type' => 'signed',
                'int' => 7,
                'dec' => 2,
            ],

            /* -------------------------------------------------
             * Start 406 End 415
             * Change Given – Change given.
             * ------------------------------------------------- */
            'change_given' => [
                'no' => 37,
                'size' => 10,
                'format' => '9(7)v9(2)±',
                'required' => false,
                'comments' => 'Change given.',
                'positions' => '406-415',
                'original_required' => 'Z',
                'type' => 'signed',
                'int' => 7,
                'dec' => 2,
            ],

            /* -------------------------------------------------
             * Start 416 End 425
             * Total Check Tendered – Check tendered amount.
             * ------------------------------------------------- */
            'total_check_tendered' => [
                'no' => 38,
                'size' => 10,
                'format' => '9(7)v9(2)±',
                'required' => false,
                'comments' => 'Check tendered amount.',
                'positions' => '416-425',
                'original_required' => 'Z',
                'type' => 'signed',
                'int' => 7,
                'dec' => 2,
            ],

            /* -------------------------------------------------
             * Start 426 End 431
             * Check Number – Check number if used.
             * ------------------------------------------------- */
            'check_number' => [
                'no' => 39,
                'size' => 6,
                'format' => '9(06)',
                'required' => false,
                'comments' => 'Check number if used.',
                'positions' => '426-431',
                'original_required' => 'Z',
                'type' => 'numeric',
                'length' => 6,
                'dec' => 0,
            ],

            /* -------------------------------------------------
             * Start 432 End 441
             * Bankcard Tendered – Bankcard tendered amount.
             * ------------------------------------------------- */
            'bankcard_tendered' => [
                'no' => 40,
                'size' => 10,
                'format' => '9(7)v9(2)±',
                'required' => false,
                'comments' => 'Bankcard tendered amount.',
                'positions' => '432-441',
                'original_required' => 'Z',
                'type' => 'signed',
                'int' => 7,
                'dec' => 2,
            ],

            /* -------------------------------------------------
             * Start 442 End 457
             * Bankcard Number – Bankcard number.
             * ------------------------------------------------- */
            'bankcard_number' => [
                'no' => 41,
                'size' => 16,
                'format' => 'x(16)',
                'required' => false,
                'comments' => 'Bankcard number.',
                'positions' => '442-457',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 16,
            ],

            /* -------------------------------------------------
             * Start 458 End 463
             * Apply To Number – AR document apply-to.
             * ------------------------------------------------- */
            'apply_to_number' => [
                'no' => 42,
                'size' => 6,
                'format' => 'x(06)',
                'required' => false,
                'comments' => 'AR document apply-to.',
                'positions' => '458-463',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 6,
            ],

            /* -------------------------------------------------
             * Start 464 End 465
             * Third Party Vendor Code – Certified third-party vendor code.
             * ------------------------------------------------- */
            'third_party_vendor_code' => [
                'no' => 43,
                'size' => 2,
                'format' => 'x(02)',
                'required' => false,
                'comments' => 'Certified third-party vendor code.',
                'positions' => '464-465',
                'original_required' => true,
                'type' => 'text',
                'length' => 2,
            ],

            /* -------------------------------------------------
             * Start 466 End 466
             * Use Estu Cost Indicator – Use ESTU cost instead of IMU.
             * ------------------------------------------------- */
            'use_estu_cost_indicator' => [
                'no' => 44,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Use ESTU cost instead of IMU.',
                'positions' => '466-466',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 467 End 467
             * Private Label Card Type – PLC card type.
             * ------------------------------------------------- */
            'private_label_card_type' => [
                'no' => 45,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'PLC card type.',
                'positions' => '467-467',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 468 End 468
             * Special Transaction Processing Flag – Special transaction processing.
             * ------------------------------------------------- */
            'special_transaction_processing_flag' => [
                'no' => 46,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Special transaction processing.',
                'positions' => '468-468',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 469 End 471
             * Private Label Card Promo Type – PLC promotion type.
             * ------------------------------------------------- */
            'private_label_card_promo_type' => [
                'no' => 47,
                'size' => 3,
                'format' => 'x(03)',
                'required' => false,
                'comments' => 'PLC promotion type.',
                'positions' => '469-471',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 3,
            ],

            /* -------------------------------------------------
             * Start 472 End 472
             * Tdx Transaction – TDX processing flag.
             * ------------------------------------------------- */
            'tdx_transaction' => [
                'no' => 48,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'TDX processing flag.',
                'positions' => '472-472',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 473 End 474
             * Unused 2 – Always spaces.
             * ------------------------------------------------- */
            'unused_2' => [
                'no' => 49,
                'size' => 2,
                'format' => 'x(02)',
                'required' => false,
                'comments' => 'Always spaces.',
                'positions' => '473-474',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 2,
            ],

            /* -------------------------------------------------
             * Start 475 End 475
             * Direct Ship – Direct ship indicator.
             * ------------------------------------------------- */
            'direct_ship' => [
                'no' => 50,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Direct ship indicator.',
                'positions' => '475-475',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 476 End 479
             * Transaction Codes – Transaction codes.
             * ------------------------------------------------- */
            'transaction_codes' => [
                'no' => 51,
                'size' => 4,
                'format' => 'x(04)',
                'required' => false,
                'comments' => 'Transaction codes.',
                'positions' => '476-479',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 4,
            ],

            /* -------------------------------------------------
             * Start 480 End 480
             * Ship Via Code – Shipping method code.
             * ------------------------------------------------- */
            'ship_via_code' => [
                'no' => 52,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Shipping method code.',
                'positions' => '480-480',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 481 End 488
             * Route Number – Route number.
             * ------------------------------------------------- */
            'route_number' => [
                'no' => 53,
                'size' => 8,
                'format' => 'x(08)',
                'required' => false,
                'comments' => 'Route number.',
                'positions' => '481-488',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 8,
            ],

            /* -------------------------------------------------
             * Start 489 End 491
             * Route Day – Route day (Mon–Sun).
             * ------------------------------------------------- */
            'route_day' => [
                'no' => 54,
                'size' => 3,
                'format' => 'x(03)',
                'required' => false,
                'comments' => 'Route day (Mon–Sun).',
                'positions' => '489-491',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 3,
            ],

            /* -------------------------------------------------
             * Start 492 End 494
             * Route Stop – Route stop.
             * ------------------------------------------------- */
            'route_stop' => [
                'no' => 55,
                'size' => 3,
                'format' => 'x(03)',
                'required' => false,
                'comments' => 'Route stop.',
                'positions' => '492-494',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 3,
            ],

            /* -------------------------------------------------
             * Start 495 End 495
             * Delivery Time Code – Delivery time code.
             * ------------------------------------------------- */
            'delivery_time_code' => [
                'no' => 56,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Delivery time code.',
                'positions' => '495-495',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 496 End 496
             * Calculate Trade Discount – Calculate trade discount flag.
             * ------------------------------------------------- */
            'calculate_trade_discount' => [
                'no' => 57,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Calculate trade discount flag.',
                'positions' => '496-496',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 497 End 497
             * Retain Sales Tax – Retain sales tax amount.
             * ------------------------------------------------- */
            'retain_sales_tax' => [
                'no' => 58,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Retain sales tax amount.',
                'positions' => '497-497',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 498 End 507
             * Pos Short Id – POS short identifier.
             * ------------------------------------------------- */
            'pos_short_id' => [
                'no' => 59,
                'size' => 10,
                'format' => 'x(10)',
                'required' => false,
                'comments' => 'POS short identifier.',
                'positions' => '498-507',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 10,
            ],

            /* -------------------------------------------------
             * Start 508 End 508
             * Auto Add Nif Items – Auto add NIF items flag.
             * ------------------------------------------------- */
            'auto_add_nif_items' => [
                'no' => 60,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Auto add NIF items flag.',
                'positions' => '508-508',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 509 End 518
             * Loyalty Id – Customer loyalty ID.
             * ------------------------------------------------- */
            'loyalty_id' => [
                'no' => 61,
                'size' => 10,
                'format' => 'x(10)',
                'required' => false,
                'comments' => 'Customer loyalty ID.',
                'positions' => '509-518',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 10,
            ],

        ];

    }

}
