<?php
declare(strict_types=1);

/** Invia una mail con allegato PDF tramite mail() (disponibile su Serverplan). */
function send_pdf_mail(string $to, string $subject, string $body, string $pdfData, string $pdfName): bool {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    $from = cfg('mail_from', 'noreply@localhost');
    $b = 'b' . bin2hex(random_bytes(8));
    $hdr = "From: " . cfg('app_name', 'Liberatorie') . " <$from>\r\n"
         . "MIME-Version: 1.0\r\n"
         . "Content-Type: multipart/mixed; boundary=\"$b\"\r\n";
    $msg = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
         . chunk_split(base64_encode($body))
         . "--$b\r\nContent-Type: application/pdf; name=\"$pdfName\"\r\nContent-Transfer-Encoding: base64\r\n"
         . "Content-Disposition: attachment; filename=\"$pdfName\"\r\n\r\n"
         . chunk_split(base64_encode($pdfData))
         . "--$b--";
    $subj = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    return @mail($to, $subj, $msg, $hdr);
}
