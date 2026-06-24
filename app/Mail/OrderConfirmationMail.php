<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        // Eager load all relations needed for PDF + email
        $order->loadMissing([
            'customer',
            'location',
            'coupon',
            'customerAddress',
            'user',
            'items.product.variants.attributeValue.attribute',
        ]);
        $this->order = $order;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Confirmed — ' . $this->order->order_no,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
        );
    }

    public function attachments(): array
    {
        try {
            $pdf = Pdf::loadView('sales.pdf', ['order' => $this->order])
                ->setPaper('a4', 'portrait');

            return [
                Attachment::fromData(
                    fn () => $pdf->output(),
                    'invoice-' . $this->order->order_no . '.pdf'
                )->withMime('application/pdf'),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}