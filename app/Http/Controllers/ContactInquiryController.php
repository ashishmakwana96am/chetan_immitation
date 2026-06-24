<?php

namespace App\Http\Controllers;

use App\Models\ContactInquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactInquiryController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'max:150'],
            'email'     => ['required', 'email', 'max:150'],
            'phone'     => ['required', 'digits:10'],
            'subject'   => ['required', 'string', 'max:200'],
            'message'   => ['required', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $inquiry = ContactInquiry::create($validator->validated());

        try {
            Mail::send('emails.contact-inquiry', ['inquiry' => $inquiry], function ($message) use ($inquiry) {
                $message->to('jenish.rising@gmail.com')
                    ->replyTo($inquiry->email, $inquiry->full_name)
                    ->subject('New Contact Inquiry - ' . $inquiry->subject);
            });

            $inquiry->update(['emailed_at' => now()]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to send email notification. Please try again later.',
            ], 500);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Thank you. Your message has been submitted successfully.',
        ], 200);
    }

    public function index()
    {
        return view('contact-inquiries.index');
    }

    public function data(Request $request)
    {
        $this->authorize('view contact inquiries');

        $query = ContactInquiry::orderBy('id', 'desc');

        if ($request->filled('emailed')) {
            if ($request->emailed == '1') {
                $query->whereNotNull('emailed_at');
            } else {
                $query->whereNull('emailed_at');
            }
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $inquiries = $query->get();

        $canDelete = auth()->user()->can('delete contact inquiries');

        $data = $inquiries->map(function ($inquiry, $index) use ($canDelete) {
            $emailHtml = '<a href="mailto:' . e($inquiry->email) . '" class="text-decoration-none">' . e($inquiry->email) . '</a>';
            $phoneHtml = '<a href="tel:' . e($inquiry->phone) . '" class="text-decoration-none">' . e($inquiry->phone) . '</a>';

            $emailedHtml = $inquiry->emailed_at
                ? '<span class="badge bg-label-success">Sent</span>'
                : '<span class="badge bg-label-warning">Pending</span>';

            $actions = '<div class="dropdown table-action-dropdown">';
            $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><span>Actions</span></button>';
            $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
            $actions .= '<a href="' . route('admin.contact-inquiries.show', $inquiry) . '" class="dropdown-item"><i class="ti ti-eye me-2"></i>View</a>';
            if ($canDelete) {
                $actions .= '<div class="dropdown-divider"></div>';
                $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.contact-inquiries.destroy', $inquiry) . '" data-row-id="inquiry-row-' . $inquiry->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
            }
            $actions .= '</div></div>';

            return [
                'id'          => $inquiry->id,
                'index'       => $index + 1,
                'full_name'   => e($inquiry->full_name),
                'email'       => $emailHtml,
                'phone'       => $phoneHtml,
                'subject'     => e($inquiry->subject),
                'emailed'     => $emailedHtml,
                'created_at'  => format_date($inquiry->created_at, 'd M Y H:i'),
                'actions'     => $actions,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function show(ContactInquiry $contactInquiry)
    {
        return view('contact-inquiries.show', compact('contactInquiry'));
    }

    public function destroy(ContactInquiry $contactInquiry)
    {
        $this->authorize('delete contact inquiries');
        $contactInquiry->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Contact inquiry deleted successfully.',
        ]);
    }
}
