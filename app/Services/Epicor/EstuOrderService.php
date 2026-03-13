<?php

namespace App\Services\Epicor;

use App\Services\Epicor\Estu\EstuBuilder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class EstuOrderService
{
    /**
     * Verifică dacă order-ul a fost deja procesat
     */
    public function alreadyProcessed(int $orderId): bool
    {
        return Cache::has("woo_processed_{$orderId}");
    }

    /**
     * Generează fișier ESTU din payload WooCommerce
     */
    public function generateFromWoo(array $order): string
    {
        if (empty($order['id'])) {
            Log::info('Estu Missing order ID: ' . ($order['id'] ?? 'NULL'));

        }

        if (empty($order['line_items'])) {

            Log::info(' Epicor Order has no line items'. ($order['id'] ?? 'NULL'));
        }

        $builder = app(EstuBuilder::class);

        $date = Carbon::parse($order['date_created'] ?? now());
        Log::info('phone',[$this->getCustomerPhone($order), $this->getCustomerEmail($order)]);
        // ================= HEADER =================
        $builder->reset()
            ->addHeader([
                'transaction_date' => $date->format('mdY'),
                'transaction_time' => $date->format('His'),
                'customer' => 3132,
                'customer_number' => 1,
                'transaction_total' => $order['total'] ?? 0,
                'total_sales_tax' => $order['total_tax'] ?? 0,
                'third_party_vendor_code' => 1,
                'ship_to_name' => $this->buildShipName($order),
                'ship_to_address_1' => $order['shipping']['address_1'] ?? '',
                'ship_to_address_2' => $order['shipping']['address_2'] ?? '',
                'ship_to_address_3' => $this->buildCityLine($order),
                'store_number' => 1,
                'instructions_1'=>$this->getCustomerEmail($order),
                'customer_telephone'=>$this->getCustomerPhone($order),
                'reference_information'=> $order['number'],
                'transaction_number' => $order['number'],
                'transaction_type'=>1,

            ]);




        // ================= DETAILS =================
        foreach ($order['line_items'] as $item) {

            if (empty($item['sku'])) {
                Log::info(' Epicor Missing SKU for line item ID'. ($order['id'] ?? 'NULL'));

            }

            if (($item['quantity'] ?? 0) <= 0) {

                Log::info('Invalid quantity for SKU'. ($order['id'] ?? 'NULL'));

            }

            $builder->addDetail([
                'sku' => $item['sku'],
                'taxable' => ($item['total_tax'] ?? 0) > 0 ? 'Y' : 'N',
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'extended_price' => $item['total'],
                'filler' => '   ',
            ]);
        }


        foreach ($order['shipping_lines'] as $item) {

            $builder->addDetail([
                'sku' => 'UPS',
                'taxable' => 'N',
                'quantity' => 1,
                'unit_price' => $item['total'],
                'extended_price' => $item['total'],
                'filler' => '   ',
            ]);
        }





        $content = $builder->build();

        // ================= SALVARE FISIER =================
        $filename = $this->generateFilename($order);

        Storage::disk('epicore')->put($filename, $content);

        // ================= MARCARE PROCESAT =================
        Cache::put("woo_processed_{$order['id']}", true, now()->addDay());

        return $filename;
    }

    /**
     * Construiește numele complet pentru shipping
     */
    protected function buildShipName(array $order): string
    {
        return trim(
            ($order['shipping']['first_name'] ?? '') . ' ' .
            ($order['shipping']['last_name'] ?? '')
        );
    }

    /**
     * Construiește linia city/state/zip
     */
    protected function buildCityLine(array $order): string
    {
        return trim(
            ($order['shipping']['city'] ?? '') . ' ' .
            ($order['shipping']['state'] ?? '') . ' ' .
            ($order['shipping']['postcode'] ?? '')
        );
    }


    protected function getShippingOrBilling(array $order, string $field): string
    {
        $shipping = trim((string)($order['shipping'][$field] ?? ''));

        if ($shipping !== '') {
            return $shipping;
        }

        $billing = trim((string)($order['billing'][$field] ?? ''));

        return $billing;
    }

    protected function getCustomerPhone(array $order): string
    {
        $phone = $this->getShippingOrBilling($order, 'phone');
        
        $digits = preg_replace('/[^0-9]/', '', $phone);

        return substr($digits, 0, 10);
    }


    protected function getCustomerEmail(array $order): string
    {
        return trim((string)($order['billing']['email'] ?? ''));
    }


    /**
     * Generează nume fișier ESTU
     */
    protected function generateFilename(array $order): string
    {
        return 'Orders/' .
            $order['number'].'.DAT';
    }
}
