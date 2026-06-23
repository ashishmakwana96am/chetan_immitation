<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function envelope(): Envelope
    {
        $statusLabels = [
            1 => 'Pending',
            2 => 'Approved',
            3 => 'Shipped',
            4 => 'Out for delivery',
            5 => 'Delivered',
            6 => 'Cancelled',
        ];
        $statusName = $statusLabels[$this->order->status] ?? 'Updated';

        return new Envelope(
            subject: 'Order #' . $this->order->order_no . ' Status: ' . $statusName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-status',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
