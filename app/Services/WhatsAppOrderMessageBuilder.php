<?php

namespace App\Services;

use App\Models\Order;

class WhatsAppOrderMessageBuilder
{
    public function url(Order $order): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $order->store?->whatsapp);

        if (! $phone) {
            return null;
        }

        return "https://wa.me/{$phone}?text=" . urlencode($this->message($order));
    }

    public function message(Order $order): string
    {
        $message = "Nuevo pedido\n";
        $message .= "Cliente: {$order->customer_name}\n";
        $message .= "Tel: {$order->customer_phone}\n";
        $message .= "Direccion: {$order->customer_address}\n";
        $message .= "Ciudad: {$order->customer_city}\n";
        $message .= "Cedula: {$order->customer_document}\n";

        if ($order->notes) {
            $message .= "Notas: {$order->notes}\n";
        }

        $message .= 'Total: $' . number_format((float) $order->total, 0, ',', '.') . "\n";

        foreach ($order->items as $item) {
            $variantText = '';

            if ($item->size) {
                $variantText .= " - Talla: {$item->size}";
            }

            if ($item->color) {
                $variantText .= " - Color: {$item->color}";
            }

            $message .= "- {$item->displayName()}{$variantText} x{$item->quantity}\n";
        }

        return $message;
    }
}
