<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Company;

class CompanyValidationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $validationCode;
    public $company;

    public function __construct($validationCode, Company $company)
    {
        $this->validationCode = $validationCode;
        $this->company = $company;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código de Validación - Registro Empresarial Chrystal',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.company-validation',
            with: [
                'validationCode' => $this->validationCode,
                'companyName' => $this->company->name,
                'companyRif' => $this->company->rif,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
