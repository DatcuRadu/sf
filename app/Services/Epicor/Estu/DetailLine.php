<?php

namespace App\Services\Epicor\Estu;

class DetailLine extends AbstractLine
{


    /**
     * Practical subset:
     * - sku (14)
     * - taxable (1) optional
     * - qty (6)
     * - unit_price (9, dec=3)
     * - extended (9, dec=3)
     */
    public function schema(): array
    {
        return [

            /* -------------------------------------------------
             * Start 1 End 1
             * Record ID – Always "D".
             * ------------------------------------------------- */
            'record_id' => [
                'no' => 1,
                'size' => 1,
                'format' => 'x(01)',
                'required' => true,
                'comments' => 'Always "D".',
                'positions' => '1-1',
                'original_required' => 'R',
                'type' => 'text',
                'length' => 1,
                'value'=>'D'
            ],

            /* -------------------------------------------------
             * Start 2 End 15
             * SKU
             * ------------------------------------------------- */
            'sku' => [
                'no' => 2,
                'size' => 14,
                'format' => 'x(14)',
                'required' => false,
                'comments' => 'Eagle (IMU) Sku or blank for descriptor lines.',
                'positions' => '2-15',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 14,
            ],

            /* -------------------------------------------------
             * Start 16 End 16
             * Item Transaction Type
             * ------------------------------------------------- */
            'item_transaction_type' => [
                'no' => 16,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Space=sale, R=return, X=exchange, D=defective return.',
                'positions' => '16-16',
                'original_required' => 'D',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 17 End 48
             * Description
             * ------------------------------------------------- */
            'description' => [
                'no' => 17,
                'size' => 32,
                'format' => 'x(32)',
                'required' => false,
                'comments' => 'Item description or IMU description.',
                'positions' => '17-48',
                'original_required' => 'D',
                'type' => 'text',
                'length' => 32,
            ],

            /* -------------------------------------------------
             * Start 49 End 49
             * Taxable
             * ------------------------------------------------- */
            'taxable' => [
                'no' => 49,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Y=item taxable, N=item not taxable.',
                'positions' => '49-49',
                'original_required' => 'D',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 50 End 50
             * Pricing Flag
             * ------------------------------------------------- */
            'pricing_flag' => [
                'no' => 50,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'D,P,Q,S,*,1-5 pricing flags.',
                'positions' => '50-50',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 51 End 51
             * Manual Price
             * ------------------------------------------------- */
            'manual_price' => [
                'no' => 51,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'M=manually priced item.',
                'positions' => '51-51',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 52 End 52
             * Estimate Use Code
             * ------------------------------------------------- */
            'estimate_use_code' => [
                'no' => 52,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Code to group like materials together.',
                'positions' => '52-52',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 53 End 53
             * Trade Discount
             * ------------------------------------------------- */
            'trade_discount' => [
                'no' => 53,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Y=trade discount allowed.',
                'positions' => '53-53',
                'original_required' => 'D',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 54 End 58
             * Discount Percent
             * ------------------------------------------------- */
            'discount_percent' => [
                'no' => 54,
                'size' => 5,
                'format' => 'v9(5)',
                'required' => false,
                'comments' => 'Item discount percent.',
                'positions' => '54-58',
                'original_required' => 'Z',
                'type' => 'numeric',
                'length' => 5,
                'dec' => 5,
            ],

            /* -------------------------------------------------
             * Start 59 End 63
             * Special Order Vendor
             * ------------------------------------------------- */
            'special_order_vendor' => [
                'no' => 59,
                'size' => 5,
                'format' => 'x(05)',
                'required' => false,
                'comments' => 'Not necessary.',
                'positions' => '59-63',
                'original_required' => 'D',
                'type' => 'text',
                'length' => 5,
            ],

            /* -------------------------------------------------
             * Start 64 End 65
             * Unit of Measure
             * ------------------------------------------------- */
            'unit_of_measure' => [
                'no' => 64,
                'size' => 2,
                'format' => 'x(02)',
                'required' => false,
                'comments' => 'Not necessary.',
                'positions' => '64-65',
                'original_required' => 'D',
                'type' => 'text',
                'length' => 2,
            ],

            /* -------------------------------------------------
             * Start 66 End 73
             * Quantity
             * ------------------------------------------------- */
            'quantity' => [
                'no' => 66,
                'size' => 8,
                'format' => '9(5)v9(3)',
                'required' => true,
                'comments' => 'Quantity of Units for the SKU.',
                'positions' => '66-73',
                'original_required' => 'R',
                'type' => 'numeric',
                'length' => 8,
                'dec' => 3,
            ],

            /* -------------------------------------------------
             * Start 74 End 81
             * Unit Price
             * ------------------------------------------------- */
            'unit_price' => [
                'no' => 74,
                'size' => 8,
                'format' => '9(5)v9(3)',
                'required' => true,
                'comments' => 'Price per Unit for the SKU.',
                'positions' => '74-81',
                'original_required' => 'R',
                'type' => 'numeric',
                'length' => 8,
                'dec' => 3,
            ],


            /* -------------------------------------------------
            * Start 82 End 89
            * Extended Price
            * ------------------------------------------------- */
            'extended_price' => [
                'no' => 82,
                'size' => 8,
                'format' => '9(6)v9(2)',
                'required' => true,
                'comments' => 'Price for all Units of the SKU. Unit Price * Quantity.',
                'positions' => '82-89',
                'original_required' => 'R',
                'type' => 'numeric',
                'length' => 8,
                'dec' => 2,
            ],

            /* -------------------------------------------------
             * Start 90 End 97
             * Unit Cost
             * ------------------------------------------------- */
            'unit_cost' => [
                'no' => 90,
                'size' => 8,
                'format' => '9(5)v9(3)',
                'required' => false,
                'comments' => 'Cost per Unit.',
                'positions' => '90-97',
                'original_required' => 'E',
                'type' => 'numeric',
                'length' => 8,
                'dec' => 3,
            ],

            /* -------------------------------------------------
             * Start 98 End 98
             * BOM SKU
             * ------------------------------------------------- */
            'bom_sku' => [
                'no' => 98,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Internal Use Only. Y/N – Item is a BOM header SKU.',
                'positions' => '98-98',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 99 End 101
             * Reference Number
             * ------------------------------------------------- */
            'reference_number' => [
                'no' => 99,
                'size' => 3,
                'format' => '9(3)',
                'required' => false,
                'comments' => 'Internal Use Only. Line item number.',
                'positions' => '99-101',
                'original_required' => 'B',
                'type' => 'numeric',
                'length' => 3,
                'dec' => 0,
            ],

            /* -------------------------------------------------
             * Start 102 End 111
             * Extended Taxable
             * ------------------------------------------------- */
            'extended_taxable' => [
                'no' => 102,
                'size' => 10,
                'format' => '9(7)v9(3)',
                'required' => false,
                'comments' => 'Internal Use Only. Total taxable amount.',
                'positions' => '102-111',
                'original_required' => 'B',
                'type' => 'numeric',
                'length' => 10,
                'dec' => 3,
            ],

            /* -------------------------------------------------
             * Start 112 End 121
             * Extended Non-Taxable
             * ------------------------------------------------- */
            'extended_non_taxable' => [
                'no' => 112,
                'size' => 10,
                'format' => '9(7)v9(3)',
                'required' => false,
                'comments' => 'Internal Use Only. Total non-taxable amount.',
                'positions' => '112-121',
                'original_required' => 'B',
                'type' => 'numeric',
                'length' => 10,
                'dec' => 3,
            ],

            /* -------------------------------------------------
             * Start 122 End 129
             * Backorder Quantity
             * ------------------------------------------------- */
            'backorder_quantity' => [
                'no' => 122,
                'size' => 8,
                'format' => '9(5)v9(3)',
                'required' => false,
                'comments' => 'Quantity of Units for the SKU.',
                'positions' => '122-129',
                'original_required' => 'Z',
                'type' => 'numeric',
                'length' => 8,
                'dec' => 3,
            ],

            /* -------------------------------------------------
             * Start 130 End 139
             * Unused (Always Blank)
             * ------------------------------------------------- */
            'unused_1' => [
                'no' => 130,
                'size' => 10,
                'format' => 'x(10)',
                'required' => false,
                'comments' => 'Always blank.',
                'positions' => '130-139',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 10,
            ],

            /* -------------------------------------------------
             * Start 140 End 140
             * Terms Discount
             * ------------------------------------------------- */
            'terms_discount' => [
                'no' => 140,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Y = Terms discount can be applied.',
                'positions' => '140-140',
                'original_required' => 'D',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 141 End 141
             * Direct Ship
             * ------------------------------------------------- */
            'direct_ship' => [
                'no' => 141,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Y = Direct Ship item.',
                'positions' => '141-141',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 142 End 171
             * Unused (Always Blank)
             * ------------------------------------------------- */
            'unused_2' => [
                'no' => 142,
                'size' => 30,
                'format' => 'x(30)',
                'required' => false,
                'comments' => 'Always blank.',
                'positions' => '142-171',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 30,
            ],

            /* -------------------------------------------------
             * Start 172 End 181
             * Export Set ID
             * ------------------------------------------------- */
            'export_set_id' => [
                'no' => 172,
                'size' => 10,
                'format' => 'x(10)',
                'required' => false,
                'comments' => 'Internal Use Only.',
                'positions' => '172-181',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 10,
            ],

            /* -------------------------------------------------
 * Start 182 End 182
 * Adder SKU Flag
 * ------------------------------------------------- */
            'adder_sku_flag' => [
                'no' => 182,
                'size' => 1,
                'format' => 'x(01)',
                'required' => false,
                'comments' => 'Default blank. 1 = Fixed Price.',
                'positions' => '182-182',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 1,
            ],

            /* -------------------------------------------------
             * Start 183 End 190
             * Master Load ID
             * ------------------------------------------------- */
            'master_load_id' => [
                'no' => 183,
                'size' => 8,
                'format' => 'x(08)',
                'required' => false,
                'comments' => 'Internal Use Only.',
                'positions' => '183-190',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 8,
            ],

            /* -------------------------------------------------
             * Start 191 End 196
             * Special Order Document Number
             * ------------------------------------------------- */
            'special_order_document_number' => [
                'no' => 191,
                'size' => 6,
                'format' => 'x(06)',
                'required' => false,
                'comments' => 'Internal Use Only.',
                'positions' => '191-196',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 6,
            ],

            /* -------------------------------------------------
             * Start 197 End 199
             * Special Order Line Number
             * ------------------------------------------------- */
            'special_order_line_number' => [
                'no' => 197,
                'size' => 3,
                'format' => 'x(03)',
                'required' => false,
                'comments' => 'Internal Use Only.',
                'positions' => '197-199',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 3,
            ],

            /* -------------------------------------------------
             * Start 200 End 201
             * S/O Type
             * ------------------------------------------------- */
            'so_type' => [
                'no' => 200,
                'size' => 2,
                'format' => 'x(02)',
                'required' => false,
                'comments' => 'Special Order Type Indicator.',
                'positions' => '200-201',
                'original_required' => 'B',
                'type' => 'text',
                'length' => 2,
            ],

            /* -------------------------------------------------
             * Start 202 End 508
             * Filler
             * ------------------------------------------------- */
            'filler' => [
                'no' => 202,
                'size' => 307,
                'format' => 'x(307)',
                'required' => true,
                'comments' => 'Filler. Required to make import records of equal length.',
                'positions' => '202-508',
                'original_required' => 'R',
                'type' => 'text',
                'length' => 307,
            ],



        ];

    }
}
