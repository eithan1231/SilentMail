<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?= misc::buildTitle("Terms of Service") ?></title>
  </head>
  <body style="width: 60%; padding-left: 16%;">
    <div>
      <a href="<?= router::instance()->getRoutePath("landing") ?>" style="color: #5f5f5f; text-decoration: none;">Home</a>

      <?php if (ses_logged_in): ?>
        <a href="<?= router::instance()->getRoutePath("mail") ?>" style="margin-left: 15px; color: #5f5f5f; text-decoration: none;">Mailbox</a>
      <?php endif; ?>
    </div>
    <h1>Terms of Service</h1>

    <p>
      Please read these Terms of Service ("Terms", "Terms of Service") carefully before using the
      <a href="<?= router::instance()->getRoutePath('landing') ?>"><?= esc(hostName) ?></a>
      website (the "Service") operated by <?= clean_name(config['projectName'])?> ("us", "we", or "our").
      Your access to and use of the Service is conditioned on your acceptance of and compliance with
      these Terms. These Terms apply to all visitors, users and others who access or use the Service.
      By accessing or using the Service you agree to be bound by these Terms. If you disagree
      with any part of the terms then you may not access the Service.
    </p>

    <h3>Termination</h3>
    <p>
      We may terminate or suspend access to our Service immediately, without prior notice or liability, for
      any reason whatsoever, including without limitation if you breach the Terms.
      All provisions of the Terms which by their nature should survive termination shall survive
      termination, including, without limitation, ownership provisions, warranty disclaimers, indemnity and
      limitations of liability.
    </p>

    <h3>Links To Other Web Sites</h3>
    <p>
      Our Service may contain links to third-party web sites or services that are not owned or controlled
      by <?= clean_name(config['projectName'])?>.
    </p>

    <p>
      <?= clean_name(config['projectName'])?> has no control over, and assumes no responsibility for, the content,
      privacy policies, or practices of any third party web sites or services. You further acknowledge and
      agree that <?= clean_name(config['projectName'])?> shall not be responsible or liable, directly or indirectly, for any
      damage or loss caused or alleged to be caused by or in connection with use of or reliance on any
      such content, goods or services available on or through any such web sites or services.
    </p>

    <h3>Changes</h3>
    <p>
      We reserve the right, at our sole discretion, to modify or replace these Terms at any time.
      What constitutes a material change will be determined at our sole discretion.
      <br>
      <a href="<?= router::instance()->getRoutePath('contactUs') ?>">Contact Us</a>
    </p>

  </body>
</html>
