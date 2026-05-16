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
        $isReservationStore = $order->store?->isReservationStore() ?? false;
        $message = ($isReservationStore ? "Nueva reserva\n" : "Nuevo pedido\n");
        $message .= "Cliente: {$order->customer_name}\n";
        $message .= "Tel: {$order->customer_phone}\n";
        $message .= ($isReservationStore ? "Direccion o referencia: " : "Direccion: ") . "{$order->customer_address}\n";
        if ($order->customer_neighborhood) {
            $message .= "Barrio: {$order->customer_neighborhood}\n";
        }
        $message .= "Ciudad: {$order->customer_city}\n";
        $message .= "Cedula: {$order->customer_document}\n";

        if ($isReservationStore) {
            $message .= 'Fecha deseada: ' . optional($order->reservation_date)->format('Y-m-d') . "\n";
            $message .= "Hora deseada: {$order->reservation_time}\n";

            if ($order->store?->business_hours) {
                $message .= "Horario de atencion: {$order->store->business_hours}\n";
            }
        }

        if ($order->notes) {
            $message .= "Notas: {$order->notes}\n";
        }

        if ($order->shipping_method) {
            $message .= "Envio: {$order->shipping_method} ($" . number_format((float) $order->shipping_cost, 0, ',', '.') . ")\n";
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

            $message .= ($isReservationStore ? "Servicio: " : "- ") . "{$item->displayName()}{$variantText} x{$item->quantity}\n";
        }

        return $message;
    }
}
