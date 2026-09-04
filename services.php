<?php
$pageTitle = "Academic Services";
include __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top:3rem;">
  <div class="section-header">
    <div class="badge badge-info" style="margin-bottom:0.8rem;">Comprehensive Writing & Technical Assistance</div>
    <h2>Our Academic Solutions</h2>
    <p>Tailored writing, coding, analysis, and proofreading services designed to meet rigorous university standards.</p>
  </div>

  <div class="grid-3">
    <div class="feature-card">
      <div class="feature-icon"><i class="fa-solid fa-pen-nib"></i></div>
      <h4>Essay Writing Assistance</h4>
      <p>Argumentative, persuasive, analytical, and narrative essays written from scratch with pristine citation standards (APA, Harvard, MLA).</p>
      <div style="margin-top:1rem;"><a href="/submit-assignment.php?type=Essay" class="btn btn-outline btn-sm">Order Essay &rarr;</a></div>
    </div>

    <div class="feature-card">
      <div class="feature-icon"><i class="fa-solid fa-book-bookmark"></i></div>
      <h4>Dissertation & Thesis</h4>
      <p>Complete proposal development, literature review synthesis, statistical data analysis (SPSS, R, Python), and methodology writeups.</p>
      <div style="margin-top:1rem;"><a href="/submit-assignment.php?type=Dissertation" class="btn btn-outline btn-sm">Order Dissertation &rarr;</a></div>
    </div>

    <div class="feature-card">
      <div class="feature-icon"><i class="fa-solid fa-code"></i></div>
      <h4>Programming & Software Engineering</h4>
      <p>Clean code in Python, Java, C++, React, SQL, and Machine Learning algorithms with step-by-step inline documentation.</p>
      <div style="margin-top:1rem;"><a href="/submit-assignment.php?type=Programming" class="btn btn-outline btn-sm">Order Coding Project &rarr;</a></div>
    </div>

    <div class="feature-card">
      <div class="feature-icon"><i class="fa-solid fa-chart-line"></i></div>
      <h4>Business Case Studies</h4>
      <p>In-depth financial modelling, SWOT/PESTEL analysis, supply chain management, and corporate strategy evaluations.</p>
      <div style="margin-top:1rem;"><a href="/submit-assignment.php?type=CaseStudy" class="btn btn-outline btn-sm">Order Case Study &rarr;</a></div>
    </div>

    <div class="feature-card">
      <div class="feature-icon"><i class="fa-solid fa-user-nurse"></i></div>
      <h4>Nursing & Healthcare Plans</h4>
      <p>Clinical risk evaluations, evidence-based care pathways, pathophysiology summaries, and SOAP notes compliant with health guidelines.</p>
      <div style="margin-top:1rem;"><a href="/submit-assignment.php?type=Nursing" class="btn btn-outline btn-sm">Order Nursing Paper &rarr;</a></div>
    </div>

    <div class="feature-card">
      <div class="feature-icon"><i class="fa-solid fa-file-shield"></i></div>
      <h4>Proofreading & Editing</h4>
      <p>Grammar refinement, academic tone enhancement, structural flow polishing, and comprehensive Turnitin plagiarism scans.</p>
      <div style="margin-top:1rem;"><a href="/submit-assignment.php?type=Editing" class="btn btn-outline btn-sm">Order Editing &rarr;</a></div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
