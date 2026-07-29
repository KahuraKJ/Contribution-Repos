<?php
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JIOKOE DIGITAL COVER | SELECT PLAN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-navy: #0f0c32;
            --accent-orange: #ff6600;
        }

        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        /* Buttons & Highlights */
        .btn-brand { background-color: var(--accent-orange); color: white; border: none; font-weight: 600; }
        .btn-brand:hover { background-color: #e65c00; color: white; transform: scale(1.02); }
        .text-orange { color: var(--accent-orange) !important; }
        .bg-navy { background-color: var(--primary-navy) !important; color: white; }

        /* Card Styling */
        .card-plan {
            border: 2px solid transparent;
            border-radius: 20px;
            transition: all 0.3s cubic-bezier(.25,.8,.25,1);
            cursor: pointer;
            background: #fff;
        }
        .card-plan:hover { 
            transform: translateY(-12px); 
            box-shadow: 0 20px 40px rgba(15, 12, 50, 0.15);
            border-color: var(--accent-orange);
        }
        
        .plan-header { 
            padding: 20px; text-align: center; color: white; font-weight: 800; 
            font-size: 1.2rem; border-top-left-radius: 18px; border-top-right-radius: 18px;
        }
        
        .bg-silver { background: #6c757d; }
        .bg-bronze { background: #8d5524; }
        .bg-gold { background: #d4af37; }
        .bg-platinum { background: var(--primary-navy); border-bottom: 4px solid var(--accent-orange); }

        .price-tag { font-size: 1.7rem; font-weight: 800; color: var(--primary-navy); display: block; }
        
        #registrationSection { display: none; }
        
        .step-indicator {
            width: 45px; height: 45px; line-height: 45px; border-radius: 50%;
            background: #e9ecef; display: inline-block; text-align: center; font-weight: bold;
            color: var(--primary-navy); border: 2px solid #dee2e6;
        }
        .step-active { background: var(--accent-orange); color: white; border-color: var(--accent-orange); }

        .dynamic-row { background: #f8f9fa; padding: 10px; border-radius: 10px; margin-bottom: 10px; }

        /* Info Card Styling */
        .info-card {
            border-top: 4px solid var(--accent-orange);
            background: white;
            border-radius: 15px;
            height: 100%;
        }
    </style>
</head>
<body>

<div class="container text-center mt-2">
    <h4 class="display-10 fw-bold text-uppercase">JIOKOE Digital Cover</h4>
    <p class="lead opacity-75">Select a plan below to start your registration.</p>
    <hr class="mb-2">


<div class="container mb-5">
    
    <div id="packageSection">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 card-plan shadow-sm" onclick="showRegistration('Silver')">
                    <div class="plan-header bg-silver text-uppercase">Silver</div>
                    <div class="card-body text-center">
                        <ul class="list-unstyled mb-4 text-start">
                            <li class="mb-2"><i class="bi bi-check2-circle text-orange me-2"></i>Personal Accident</li>
                            <li class="mb-2"><i class="bi bi-check2-circle text-orange me-2"></i>Third Party Insurance</li>
                        </ul>
                        <div class="py-3 border-top border-light">
                            <span class="text-muted small">Annual: KSh 5,250</span>
                            <span class="price-tag mt-1">KSh 14 <small class="fs-6 fw-normal">/Day</small></span>
                        </div>
                        <button class="btn btn-outline-dark w-100 mt-3 rounded-pill">Choose Silver</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card h-100 card-plan shadow-sm" onclick="showRegistration('Bronze')">
                    <div class="plan-header bg-bronze text-uppercase">Bronze</div>
                    <div class="card-body text-center">
                        <ul class="list-unstyled mb-4 text-start">
                            <li class="mb-2"><i class="bi bi-check2-circle text-orange me-2"></i>Personal Accident</li>
                            <li class="mb-2"><i class="bi bi-check2-circle text-orange me-2"></i>Comprehensive Ins.</li>
                        </ul>
                        <div class="py-3 border-top border-light">
                            <span class="text-muted small">Annual: KSh 11,700</span>
                            <span class="price-tag mt-1">KSh 32 <small class="fs-6 fw-normal">/Day</small></span>
                        </div>
                        <button class="btn btn-outline-dark w-100 mt-3 rounded-pill">Choose Bronze</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card h-100 card-plan shadow-sm" style="background: #fff8f4;" onclick="showRegistration('Gold')">
                    <div class="plan-header bg-gold text-uppercase">Gold</div>
                    <div class="card-body text-center">
                        <ul class="list-unstyled mb-4 text-start">
                            <li class="mb-2"><i class="bi bi-check2-circle text-orange me-2"></i>Personal Accident</li>
                            <li class="mb-2"><i class="bi bi-check2-circle text-orange me-2"></i>Comprehensive Ins.</li>
                            <li class="mb-2"><i class="bi bi-check2-circle text-orange me-2"></i>Last Expense (Nuclear)</li>
                        </ul>
                        <div class="py-3 border-top border-light">
                            <span class="text-muted small">Annual: KSh 14,800</span>
                            <span class="price-tag mt-1 text-orange">KSh 41 <small class="fs-6 fw-normal">/Day</small></span>
                        </div>
                        <button class="btn btn-brand w-100 mt-3 rounded-pill">Most Popular</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card h-100 card-plan shadow-sm" onclick="showRegistration('Platinum')">
                    <div class="plan-header bg-platinum text-uppercase text-orange">Platinum</div>
                    <div class="card-body text-center">
                        <ul class="list-unstyled mb-4 text-start">
                            <li class="mb-2 text-navy fw-bold"><i class="bi bi-plus-circle-fill text-orange me-2"></i>All Covers Included</li>
                            <li class="mb-2"><i class="bi bi-check2-circle text-orange me-2"></i>Last Expense (Extended)</li>
                        </ul>
                        <div class="py-3 border-top border-light">
                            <span class="text-muted small">Annual: KSh 17,000</span>
                            <span class="price-tag mt-1">KSh 47 <small class="fs-6 fw-normal">/Day</small></span>
                        </div>
                        <button class="btn btn-outline-dark w-100 mt-3 rounded-pill">Choose Platinum</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5 g-4">
            <div class="col-md-4">
                <div class="info-card p-4 shadow-sm">
                    <h5 class="fw-bold text-navy mb-3"><i class="bi bi-file-earmark-text text-orange me-2"></i>Subscription Certificate</h5>
                    <ul class="list-unstyled small text-muted">
                        <li class="mb-2"><i class="bi bi-check2 text-orange me-2"></i>Issued to every member after activation</li>
                        <li class="mb-2"><i class="bi bi-check2 text-orange me-2"></i>Confirms the selected cover package</li>
                        <li class="mb-2"><i class="bi bi-check2 text-orange me-2"></i>Serves as official proof of subscription</li>
                        <li><i class="bi bi-check2 text-orange me-2"></i>Updated upon package upgrade</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card p-4 shadow-sm">
                    <h5 class="fw-bold text-navy mb-3"><i class="bi bi-arrow-repeat text-orange me-2"></i>Upgrades & Flexibility</h5>
                    <ul class="list-unstyled small text-muted">
                        <li class="mb-2"><i class="bi bi-check2 text-orange me-2"></i>Members may upgrade anytime by topping up the difference</li>
                        <li class="mb-2"><i class="bi bi-check2 text-orange me-2"></i>Payments are cumulative</li>
                        <li><i class="bi bi-check2 text-orange me-2"></i>Payments stop automatically once annual cover is fully paid</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card p-4 shadow-sm">
                    <h5 class="fw-bold text-navy mb-3"><i class="bi bi-info-circle text-orange me-2"></i>Important Notes</h5>
                    <ul class="list-unstyled small text-muted">
                        <li class="mb-2"><i class="bi bi-check2 text-orange me-2"></i>Daily and Monthly figures are calculated from the annual cover</li>
                        <li class="mb-2"><i class="bi bi-check2 text-orange me-2"></i>No hidden charges</li>
                        <li><i class="bi bi-check2 text-orange me-2"></i>Transparent and member-friendly</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-5 p-4 bg-navy rounded-4 text-center text-white shadow">
            <h5 class="fw-bold mb-2"><i class="bi bi-headset text-orange me-2"></i>For Enquiries & Support</h5>
            <p class="mb-3 opacity-75">Contact your JIOKOE Digital Cover representative today.</p>
           <div class="d-flex justify-content-center gap-3">
    <a href="tel:+254710353974" class="btn btn-brand btn-sm px-4 rounded-pill">
        <i class="bi bi-telephone-fill me-2"></i>Call Representative
    </a>
    
    <a href="https://wa.me/254710353974?text=Hi,%20I%20need%20assistance%20with%20JIOKOE%20Digital%20Cover%20registration." 
       target="_blank" 
       class="btn btn-outline-light btn-sm px-4 rounded-pill">
        <i class="bi bi-whatsapp me-2"></i>WhatsApp Support
    </a>
</div>
        </div>
    </div>

    <div id="registrationSection" class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5">
                    <button class="btn btn-sm btn-link mb-3 text-decoration-none" onclick="goBack()">
                        <i class="bi bi-arrow-left"></i> Change Plan
                    </button>
                    
                    <div class="text-center mb-4">
                        <span class="step-indicator step-active">2</span>
                        <h3 class="mt-3 fw-bold" style="color: var(--primary-navy);">Complete Registration</h3>
                        <p class="text-muted">You are registering for the <span id="selectedPlanBadge" class="badge bg-navy">PLAN</span> Package</p>
                    </div>

                    <form action="save_beneficiaries.php" method="POST">
                        <input type="hidden" name="selected_plan" id="planInput">

                        <div class="mb-4">
                            <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary">Parental Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Father's Full Name</label>
                                    <input type="text" name="father" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Mother's Full Name</label>
                                    <input type="text" name="mother" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5 class="fw-bold border-bottom pb-2 mb-3 text-primary">Spouse & Dependents</h5>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Spouse Name (If applicable)</label>
                                <input type="text" name="spouse" class="form-control" placeholder="Full name">
                            </div>

                            <div id="childrenContainer"></div>
                            
                            <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="addChildBtn">
                                <i class="bi bi-plus-circle me-1"></i> Add a Child
                            </button>

                            <div id="wivesContainer"></div>

                            <button type="button" class="btn btn-outline-secondary btn-sm" id="addWifeBtn">
                                <i class="bi bi-plus-circle me-1"></i> Add Additional Wife
                            </button>
                        </div>

                        <button type="submit" class="btn btn-brand btn-lg w-100 py-3 text-uppercase fw-bold mt-4" style="background-color: var(--primary-navy); color: white;">
                            Finalize Registration <i class="bi bi-shield-check ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showRegistration(planName) {
        document.getElementById('packageSection').style.display = 'none';
        document.getElementById('registrationSection').style.display = 'flex';
        document.getElementById('selectedPlanBadge').innerText = planName.toUpperCase();
        document.getElementById('planInput').value = planName;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function goBack() {
        document.getElementById('registrationSection').style.display = 'none';
        document.getElementById('packageSection').style.display = 'block';
    }

    document.getElementById('addChildBtn').addEventListener('click', function() {
        const container = document.getElementById('childrenContainer');
        const div = document.createElement('div');
        div.className = 'dynamic-row d-flex gap-2';
        div.innerHTML = `
            <input type="text" name="child_name[]" class="form-control" placeholder="Child Name" required>
            <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>
        `;
        container.appendChild(div);
    });

    document.getElementById('addWifeBtn').addEventListener('click', function() {
        const container = document.getElementById('wivesContainer');
        const div = document.createElement('div');
        div.className = 'dynamic-row d-flex gap-2';
        div.innerHTML = `
            <input type="text" name="wife_name[]" class="form-control" placeholder="Additional Wife Name" required>
            <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>
        `;
        container.appendChild(div);
    });
</script>

</body>
</html>