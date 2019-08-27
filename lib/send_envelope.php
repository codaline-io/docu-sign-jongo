<?php
include_once 'base.php';

class SendEnvelope extends Base {
    protected static $signer_mail;
    protected static $signer_name;
    protected static $pdf_title;
    protected static $pdf_base64_content;
    protected static $pos_x;
    protected static $pos_y;
    protected static $page;
    protected static $email_subject;

    public function __construct($client, $signer_mail, $signer_name, $pdf_base64_content, $pdf_title, $pos_x, $pos_y, $page, $return_url, $email_subject) {
        parent::__construct($client, $return_url);
        self::$signer_mail = $signer_mail;
        self::$signer_name = $signer_name;
        self::$pdf_title = $pdf_title;
        self::$pdf_base64_content = $pdf_base64_content;
        self::$pos_x = $pos_x;
        self::$pos_y = $pos_y;
        self::$page = $page;
        self::$email_subject = $email_subject ?: 'Ein Dokument wartet auf Ihre Unterschrift';
    }

    public function send() {
        $this->checkToken();

        # document (pdf) - has sign here anchor tag /sn1/
        #
        # The envelope has one recipient.
        # recipient 1 - signer
        # The envelope will be sent first to the signer.
        #
        # create the envelope definition
        $envelope_definition = new \DocuSign\eSign\Model\EnvelopeDefinition([
            'email_subject' => self::$email_subject
        ]);

        $signer_name = self::$signer_name;
        $signer_email = self::$signer_mail;
        $pdf_base64_content = self::$pdf_base64_content;
        $pdf_title = self::$pdf_title;

        # Create the document models
        $document = new \DocuSign\eSign\Model\Document([  # create the DocuSign document object
            'document_base64' => $pdf_base64_content,
            'name' => $pdf_title, # can be different from actual file name
            'file_extension' => 'pdf', # many different document types are accepted
            'document_id' => '1' # a label used to reference the doc
        ]);
        # The order in the docs array determines the order in the envelope
        $envelope_definition->setDocuments([$document]);

        $signers = array();
        foreach ($signer_email as $index => $email) {
            # Create the signer recipient model
            $signer = new \DocuSign\eSign\Model\Signer([
                'email' => $email, 'name' => $signer_name[$index],
                'recipient_id' => $index + 1
            ]);
            if ($index == 0) {
                $signer->setClientUserId($index + 1);
            }

            # Create signHere fields (also known as tabs) on the documents,
            # We're using anchor (autoPlace) positioning
            #
            # The DocuSign platform searches throughout your envelope's
            # documents for matching anchor strings.
            $sign_here = new \DocuSign\eSign\Model\SignHere([
                'page_number' => self::$page,
                'document_id' => '1',
                'x_position' => self::$pos_x[$index],
                'y_position' => self::$pos_y[$index]
            ]);

            # Add the tabs model (including the sign_here tabs) to the signer
            # The Tabs object wants arrays of the different field/tab types
            $signer->setTabs(new \DocuSign\eSign\Model\Tabs([
                'sign_here_tabs' => [$sign_here]]
            ));

            array_push($signers, $signer);
        }

        # Add the recipients to the envelope object
        $recipients = new \DocuSign\eSign\Model\Recipients([
            'signers' => $signers
        ]);
        $envelope_definition->setRecipients($recipients);
        # Request that the envelope be sent by setting |status| to "sent".
        # To request that the envelope be created as a draft, set to "created"
        $envelope_definition->setStatus("sent");

        $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi(self::$apiClient);
        $results = $envelopeApi->createEnvelope(self::$accountID, $envelope_definition);

        return $results;
    }
}
