<?php
include 'header.php';
include 'connect.php';

include 'connect.php';

if (!isset($_SESSION['id_no'])) {
    header('Location: index.php'); // Redirect to login if not logged in
    exit;
}

// 2. ONLY get the ID from the secure session data.
$id_no = $_SESSION['id_no'];



$existingData = [];
if ($id_no) {
    $stmt = $con->prepare("SELECT * FROM residential WHERE id_no = ?");
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingData = $result->fetch_assoc() ?: [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Address Form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
 <style>
        :root {
            --navy-blue: #03042b;
            --orange: #e85005;    
        }
        
       
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 31, 76, 0.1); /* Subtle shadow using navy blue color */
            margin-top: 20px;
        }

        .card-header {
            background-color: var(--navy-blue);
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }

        .form-group-title {
            color: var(--orange); /* Section titles in Orange */
            border-bottom: 2px solid var(--navy-blue);
            padding-bottom: 5px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .form-select, .form-control {
            border-radius: 8px;
            border-color: #ced4da;
        }
        
        /* Submit Button Styling */
        .btn-theme {
            background-color: var(--orange);
            color: var(--navy-blue);
            font-weight: bold;
            border: 2px solid var(--navy-blue);
            border-radius: 8px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-theme:hover {
            background-color: #ffaa33; /* Slightly lighter orange on hover */
            border-color: var(--navy-blue);
            transform: translateY(-2px);
            color: var(--navy-blue);
        }
    </style>
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-6"> 
            
            <div class="card shadow">
                <div class="card-header text-center">
                    CONFIRM THE ADDRESS DETAILS.
                </div>
                <div class="card-body p-4">
                    <form id="addressForm" method="POST" action="process_address.php">
                        <input type="hidden" name="id_no" value="<?= htmlspecialchars($id_no) ?>">

                        <h5 class="form-group-title">Residential / Current Area Information</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="county" class="form-label">County</label>
                                <select class="form-select" id="county" name="county" required>
                                    <option value="">Select County</option>
                                    <?php if ($current_county): ?>
                                        <option value="<?= htmlspecialchars($current_county) ?>" selected><?= htmlspecialchars($current_county) ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="sub_county" class="form-label">Sub-County</label>
                                <select class="form-select" id="sub_county" name="sub_county" required>
                                    <option value="">Select Sub-County</option>
                                    <?php if ($current_sub_county): ?>
                                        <option value="<?= htmlspecialchars($current_sub_county) ?>" selected><?= htmlspecialchars($current_sub_county) ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="ward" class="form-label">Ward</label>
                                <select class="form-select" id="ward" name="ward" required>
                                    <option value="">Select Ward</option>
                                    <?php if ($current_ward): ?>
                                        <option value="<?= htmlspecialchars($current_ward) ?>" selected><?= htmlspecialchars($current_ward) ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="estate" class="form-label">Estate / Locality</label>
                                <input type="text" class="form-control" id="estate" name="estate"
                                       value="<?= htmlspecialchars($existingData['estate'] ?? '') ?>"
                                       placeholder="Current Estate/Locality" required>
                            </div>
                        </div>

                        <h5 class="form-group-title mt-4">Permanent Home Address Information</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="home_county" class="form-label">Home County</label>
                                <select class="form-select" id="home_county" name="home_county" required>
                                    <option value="">Select County</option>
                                    <?php if ($home_county): ?>
                                        <option value="<?= htmlspecialchars($home_county) ?>" selected><?= htmlspecialchars($home_county) ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="home_sub_county" class="form-label">Home Sub-County</label>
                                <select class="form-select" id="home_sub_county" name="home_sub_county" required>
                                    <option value="">Select Sub-County</option>
                                    <?php if ($home_sub_county): ?>
                                        <option value="<?= htmlspecialchars($home_sub_county) ?>" selected><?= htmlspecialchars($home_sub_county) ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="home_ward" class="form-label">Home Ward</label>
                                <select class="form-select" id="home_ward" name="home_ward" required>
                                    <option value="">Select Ward</option>
                                    <?php if ($home_ward): ?>
                                        <option value="<?= htmlspecialchars($home_ward) ?>" selected><?= htmlspecialchars($home_ward) ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="home_estate" class="form-label">Home Estate / Locality</label>
                                <input type="text" class="form-control" id="home_estate" name="home_estate"
                                       value="<?= htmlspecialchars($existingData['home_estate'] ?? '') ?>"
                                       placeholder="Permanent Home Estate/Locality" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-theme btn-lg mt-3 w-100">Confirm</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
       const countyData = {
    "Baringo": {
        "Baringo Central": ["Kabarnet Town Ward", "Sacho Ward", "Tenges Ward", "Kapropita Ward", "Ewalel Chapel Ward"],
        "Baringo North": ["Kabartonjo Ward", "Bartabwa Ward", "Saimo-Kipsaraman Ward", "Saimo-Soi Ward", "Barwessa Ward"],
        "Baringo South": ["Marigat Ward", "Ilchamus Ward", "Mochongoi Ward", "Mukutani Ward"],
        "Eldama Ravine": ["Eldama Ravine Ward", "Lembus Kwen Ward", "Lembus Ward", "Mogotio Ward", "Emining Ward"],
        "Mogotio": ["Mogotio Ward", "Kisanana Ward", "Sandai Ward", "Emsos Ward"],
        "Tiaty": ["Kolowa Ward", "Tirioko Ward", "Ripkwo/Churo/Barsombe Ward", "Loyamorok Ward", "Nukinyang Ward", "Silale Ward", "Loiyangalani Ward"]
    },
    "Bomet": {
        "Bomet Central": ["Mutarakwa Ward", "Kipreres Ward", "Ndani Ward", "Singorwet Ward", "Chesoen Ward"],
        "Bomet East": ["Merigi Ward", "Kembu Ward", "Longisa Ward", "Kipsonoi Ward", "Chemaner Ward"],
        "Chepalungu": ["Chepalungu Ward", "Kong'asis Ward", "Siongiroi Ward", "Nyangores Ward", "Taboino Ward"],
        "Konoin": ["Boito Ward", "Embomos Ward", "Kimulot Ward", "Chepchabas Ward", "Tungunoi Ward"],
        "Sotik": ["Ndanai/Abosi Ward", "Manaret/Kipchoge Ward", "Kaplong Ward", "Mutito/Ainamoi Ward", "Chebilat Ward"]
    },
    "Bungoma": {
        "Bumula": ["Bumula Ward", "Khasoko Ward", "Kimaeti Ward", "South Bukusu Ward", "Siboti Ward"],
        "Kabuchai": ["Kabuchai Ward", "Chwele/Kabuchai Ward", "Mukuyuni Ward", "Cheptais Ward", "Kibingei Ward"],
        "Kanduyi": ["Bukembe West Ward", "East Sang'alo Ward", "Kanduyi Ward", "Marakaru/Tuuti Ward", "Milima Ward", "Sinoko Ward", "Tuuti/Marakaru Ward", "Khalaba Ward", "Musikoma Ward", "Township Ward"],
        "Kimilil": ["Kimilil Ward", "Kamukuywa Ward", "Kimilili Ward", "Maeni Ward"],
        "Mt. Elgon": ["Chepyuk Ward", "Kaptama Ward", "Kapsokwony Ward", "Koitilil Ward", "Cheptais Ward", "Chemoge Ward"],
        "Sirisia": ["Sirisia Ward", "Lwandanyi Ward", "Malakisi/South Khasoko Ward"],
        "Tongaren": ["Tongaren Ward", "Ndalu Ward", "Mbakalo Ward", "Mbakalo/Kamukuywa Ward", "Naitiri/Kabuyefwe Ward"],
        "Webuye East": ["Marakaru Ward", "Mihuu Ward", "Ndivisi Ward"],
        "Webuye West": ["Sitikho Ward", "Misikhu Ward", "Bokoli Ward"]
    },
    "Busia": {
        "Budalangi": ["Ruambwa Ward", "Bunyala Central Ward", "Bunyala North Ward", "Bunyala South Ward"],
        "Butula": ["Marachi East Ward", "Marachi West Ward", "Kingandole Ward", "Mayenje Ward", "Butula Ward", "Nanguba Ward"],
        "Funyula": ["Funyula Ward", "Namboboto Ward", "Wakhungu Ward", "Obaro Ward"],
        "Nambale": ["Nambale Township Ward", "Burumba Ward", "Bukhayo North/Walatsi Ward", "Bukhayo Central Ward", "Mwira Ward"],
        "Teso North": ["Ang'urai East Ward", "Ang'urai South Ward", "Ang'urai North Ward", "" /* Add other wards */],
        "Teso South": ["Amukura East Ward", "Amukura West Ward", "Chakol South Ward", "Chakol North Ward", "Aloete Ward"]
    },
    "Elgeyo-Marakwet": {
        "Keiyo North": ["Iten/Tambach Ward", "Kamariny Ward", "Samweno Ward", "Chepkorio Ward"],
        "Keiyo South": ["Metkei Ward", "Kaptarakwa Ward", "Soy South Ward", "Kabiemit Ward"],
        "Marakwet East": ["Embobut/Embulot Ward", "Sengwer Ward", "Kapyego Ward", "Sambirir Ward"],
        "Marakwet West": ["Kapsowar Ward", "Arror Ward", "Cherang'any/Chebororwa Ward", "Lelan Ward", "Moiben/Kuserwo Ward"]
    },
    "Embu": {
        "Manyatta": ["Nginda Ward", "Mbeti North Ward", "Mbeti South Ward", "Kithimu Ward", "Manyatta Ward"],
        "Mbeere North": ["Evurore Ward", "Nthawa Ward", "Muminji Ward", "Gitare Ward", "Mavuria Ward"],
        "Mbeere South": ["Mwea Ward", "Makima Ward", "Mbeti South Ward", "Kiritiri Ward", "Kiambere Ward"],
        "Runyenjes": ["Runyenjes Central Ward", "Gaturi South Ward", "Kagaari South Ward", "Kagaari North Ward"]
    },
    "Garissa": {
        "Dadaab": ["Dadaab Ward", "Liboi Ward", "Damajale Ward", "Abakaile Ward"],
        "Fafi": ["Bura Ward", "Dekaharia Ward", "Jarajila Ward", "Fafi Ward", "Nanighi Ward"],
        "Garissa": ["Township Ward", "Biashara Ward", "Galbet Ward", "Waberi Ward"],
        "Hulugho": ["Hulugho Ward", "Sangailu Ward", "Ijara Ward"],
        "Ijara": ["Ijara Ward", "Sangailu Ward", "Korakora Ward", "Hulugho Ward"],
        "Lagdera": ["Benane Ward", "Modogashe Ward", "Sabena Ward", "Lagdera Ward"]
    },
    "Homa Bay": {
        "Homabay Town": ["Homa Bay Town Central Ward", "Homa Bay Town East Ward", "Homa Bay Town West Ward"],
        "Kabondo Kasipul": ["Kabondo East Ward", "Kabondo West Ward", "Kodera Ward", "Kasipul West Ward"],
        "Karachwonyo": ["North Karachwonyo Ward", "Central Karachwonyo Ward", "Kendu Bay Town Ward", "Kanyadoto Ward", "Kibiri Ward",
  "Wang'chieng' Ward"],
        "Kasipul": ["East Kasipul Ward", "West Kasipul Ward", "South Kasipul Ward", "Wang'chieng Ward"],
        "Mbita": ["Mfangano Island Ward", "Rusinga Island Ward", "Gembe Ward", "Mbita Ward", "Gwassi North Ward", "Gwassi South Ward"],
        "Ndhiwa": ["Kanyikela Ward", "Kanyamwa Kosewe Ward", "Kwabwai Ward", "Ndhiwa Ward", "Kanyamwa Kologi Ward"],
        "Rangwe": ["Rangwe Ward", "Kanyaluo Ward", "Gem Ward", "Kamuga Ward"],
        "Suba": ["Gwassi North Ward", "Gwassi South Ward", "Ruma Kanyamwa Ward", "Lambwe Ward", "" /* Add other wards */]
    },
    "Isiolo": {
        "Isiolo": ["Bulapesa Ward", "Chari Ward", "Wabera Ward", "Burat Ward"],
        "Garbatulla": ["Garbatulla Ward", "Kinna Ward", "Modogashe Ward"],
        "Merti": ["Cherab Ward", "Gafarsa Ward", "Oldonyiro Ward"]
    },
    "Kajiado": {
        "Isinya": ["Isinya Ward", "Kaputiei North Ward", "Oloosirkon/Sholinke Ward", "Lenkek Ward"],
        "Kajiado Central": ["Kajiado Township Ward", "Imaroro Ward", "Poka/Mashuuru Ward", "Dalalekutuk Ward"],
        "Kajiado North": ["Ngong Ward", "Olkeri Ward", "Nkaimurunya Ward", "Oloolua Ward", "Kajiado West Ward"],
        "Loitokitok": ["Rombo Ward", "Kimana Ward", "Illasit Ward", "Loitokitok Ward", "Entonet/Lenkisin Ward"],
        "Mashuuru": ["Mashuuru Ward", "Ildamat Ward", "Meto Ward", "Kitengela Ward"]
    },
    "Kakamega": {
        "Butere": ["Marama North Ward", "Marama West Ward", "Marama Central Ward", "Marama South Ward"],
        "Ikolomani": ["Shisiru Ward", "Idakho North Ward", "Idakho South Ward", "Lirhanda Ward"],
        "Khwisero": ["Khwisero Ward", "Luanda/Luandeti Ward", "Koyonzo Ward", "Chegulo Ward"],
        "Likuyani": ["Likuyani Ward", "Likuyani/Matunda Ward", "Sikho Ward", "Soy Ward"],
        "Lugari": ["Lugari Ward", "Lumakanda Ward", "Likuyani Ward", "Chekalini Ward", "Likuyani/Matunda Ward"],
        "Lurambi": ["Lurambi Ward", "Butsotso East Ward", "Butsotso Central Ward", "Butsotso West Ward", "Murhanda Ward", "" /* Add other wards */],
        "Malava": ["East Kabras Ward", "West Kabras Ward", "North Kabras Ward", "South Kabras Ward", "Chemuche Ward", "" /* Add other wards */],
        "Matungu": ["Koyonzo Ward", "Khalaba Ward", "Eswani Ward", "Kholera Ward"],
        "Mumias East": ["East Wanga Ward", "Malia Ward", "Echesa Ward"],
        "Mumias West": ["Mumias Central Ward", "Mumias North Ward", "Mumias South Ward", "Musanda Ward"],
        "Navakholo": ["Navakholo Ward", "Bunyala East Ward", "Bunyala North Ward", "Bunyala West Ward"],
        "Shinyalu": ["Shinyalu Ward", "Isukha East Ward", "Isukha North Ward", "Isukha South Ward", "Ilesi Ward"]
    },
    "Kericho": {
        "Ainamoi": ["Ainamoi Ward", "Kapsoit Ward", "Kipchebor Ward", "Tebesonik Ward", "Chemogos Ward"],
        "Belgut": ["Belgut Ward", "Kabianga Ward", "Waldai Ward", "Chepkembeli Ward"],
        "Bureti": ["Litein Ward", "Kimulot Ward", "Kipreres Ward", "Kamelil Ward", "Tebesonik Ward", "Roret Ward", "Cheboin Ward"],
        "Kipkelion East": ["Londiani Ward", "Kedowa/Saniak Ward", "Kipkelion Ward", "Tendwet Ward"],
        "Kipkelion West": ["Chilchila Ward", "Kunyak Ward", "Kiptere Ward", "Chepseon Ward"],
        "Soin Sigowet": ["Soin Ward", "Sigowet Ward", "Kaplelartet Ward", "Soliat Ward"]
    },
    "Kiambu": {
        "Gatundu North": ["Mang'u Ward", "Chania Ward", "Gatukuyu Ward", "Ndarugo Ward"],
        "Gatundu South": ["Kiamwangi Ward", "Kiamworia Ward", "Ituru Ward", "Muiru Ward"],
        "Githunguri": ["Githunguri Ward", "Ngewa Ward", "Komothai Ward", "Githiga Ward",
  "Ikinu Ward"],
        "Juja": ["Juja Ward", "Kalimoni Ward", "Witeithie Ward", "Murera Ward"],
        "Kabete": ["Gitaru Ward", "Kabete Ward", "Kinoo Ward", "Uthiru/Muguga Ward", "Ngecha Tigoni Ward"],
        "Kiambaa": ["Karuri Ward", "Ndenderu Ward", "Muchatha Ward", "Cianda Ward", "Kihara Ward"],
        "Kiambu Town": ["Township Ward", "Ngecha Ward", "Ndumberi Ward", "Ting'ang'a Ward"],
        "Kikuyu": ["Kikuyu Ward", "Karai Ward", "Nachu Ward", "Sigona Ward", "Limuru East Ward"],
        "Limuru": ["Limuru East Ward", "Limuru Central Ward", "Ngecha Ward", "Biashara Ward"],
        "Ruiru": ["Biashara Ward", "Kahawa Sukari Ward", "Kahawa Wendani Ward", "Githurai Ward", "Gitothua Ward", "Gatongora Ward", "Ruiru Central Ward"],
        "Thika Town": ["Township Ward", "Parklands Ward", "Kamenu Ward", "Hospital Ward", "Ngewa Ward", "Witeithie Ward"],
        "Lari": ["Lari Ward", "Kijabe Ward", "Kamburu Ward", "Nyanduma Ward", "Kijabe Ward"]
    },
    "Kilifi": {
        "Ganze": ["Bamba Ward", "Ganze Ward", "Jaribuni Ward", "Mwahera Ward"],
        "Kaloleni": ["Kaloleni Ward", "Mariakani Ward", "Mavueni Ward", "Kayafungo Ward"],
        "Kilifi North": ["Tezo Ward", "Matsangoni Ward", "Kibarani Ward", "Watamu Ward", "Dabaso Ward"],
        "Kilifi South": ["Shimo La Tewa Ward", "Chasimba Ward", "Mtepeni Ward", "Junju Ward"],
        "Magarini": ["Gongoni Ward", "Marafa Ward", "Magarini Ward", "Sabaki Ward", "Garashi Ward"],
        "Malindi": ["Shella Ward", "Malindi Town Ward", "Ganda Ward", "Kakuyuni Ward", "Jilore Ward", "Langobaya Ward"],
        "Rabai": ["Rabai/Mwakirunge Ward", "Kambe/Ribe Ward", "Jibana Ward"]
    },
    "Kirinyaga": {
        "Kirinyaga Central": ["Kanyekini Ward", "Kerugoya/Kutus Ward", "Mutira Ward", "Nyangati Ward"],
        "Kirinyaga East": ["Gichugu Ward", "Kabare Ward", "Kanyenya-ini Ward", "Ngariama Ward"],
        "Kirinyaga West": ["Mwea Ward", "Thiba Ward", "Wamumu Ward", "Wang'uru Ward"],
        "Mwea East": ["Kangai Ward", "Mwea Ward", "Wang'uru Ward", "Thiba Ward"],
        "Mwea West": ["Mutithi Ward", "Murinduko Ward", "Nyangati Ward", "Thigirigi Ward"]
    },
    "Kisii": {
        "Bomachoge Borabu": ["Bobaracho Ward", "Bomachoge Ward", "Kiabonyoru Ward", "Misesi Ward"],
        "Bomachoge Chache": ["Bogetenga Ward", "Bokeira Ward", "Tendere Ward", "Kenyenya Ward"],
        "Bobasi": ["Bobasi Ward", "Sameta Ward", "Basii Ward", "Nyacheki Ward", "" /* Add other wards */],
        "Bokimira": ["Bokimira Ward", "Gesicho Ward", "Masaba South Ward"],
        "Etago": ["Etago Ward", "Kegogi Ward", "Nyacheki Ward"],
        "Kitutu Chache North": ["Mosocho Ward", "Marani Ward", "Mwamonari Ward", "Kitutu Central Ward"],
        "Kitutu Chache South": ["Kisii Central Ward", "Nyansira Ward", "Nyamataro Ward", "Jogoo Ward", "Sensi Ward"],
        "Nyaribari Chache": ["Bobaracho Ward", "Kiogoro Ward", "Mosocho Ward", "Central Ward", "" /* Add other wards */],
        "Nyaribari Masaba": ["Kiogoro Ward", "Mwarembo Ward", "Gesusu Ward", "Ibacho Ward"],
        "Sameta": ["Sameta Ward", "Ichuni Ward", "Nyakoe Ward"],
        "South Mugirango": ["Bogetenga Ward", "Kenyenya Ward", "Moticho Ward", "Omobera Ward", "Boikanga Ward"]
    },
    "Kisumu": {
        "Kisumu Central": ["Kondele Ward", "Kaloleni Ward", "Shanzu Ward", "Nyalenda 'A' Ward", "Nyalenda 'B' Ward", "Railways Ward"],
        "Kisumu East": ["Kolwa East Ward", "Kolwa Central Ward", "Manyatta B Ward","Nyalenda A Ward",
  "Railways Ward","Kabonyo/Kajulu Ward"],
        "Kisumu West": ["Kisumu North Ward", "South West Kisumu Ward", "Central Kisumu Ward","North West Kisumu Ward",
  "West Kisumu Ward"],
        "Muhoroni": ["Chemelil/Tamu Ward", "Koru Ward", "Miwani Ward", "Ombeyi Ward"],
        "Nyakach": ["South Nyakach Ward", "Central Nyakach Ward", "North Nyakach Ward", "West Nyakach Ward", "East Nyakach Ward"],
        "Nyando": ["Awasi/Onjiko Ward", "Ahero Ward", "Kano/Kolwa Ward", "Kabonyo/Kajulu Ward"],
        "Seme": ["East Seme Ward", "West Seme Ward", "North Seme Ward", "Central Seme Ward"]
    },
    "Kitui": {
        "Ikutha": ["Ikutha Ward", "Kanziko Ward", "Mutomo/Kibwezi Ward", "" /* Add other wards */],
        "Katulani": ["Katulani Ward", "Kisasi Ward", "Kanyangi Ward", "Kyangwithya East Ward"],
        "Kisasi": ["Kisasi Ward", "Kanyangi Ward", "Kyangwithya West Ward", "Miambani Ward"],
        "Kitui Central": ["Kitui Central Ward", "Mulango Ward", "Kyangwithya East Ward", "Kyangwithya West Ward"],
        "Kitui East": ["Mutito/Mbitini Ward", "Kitui East Ward", "Endau/Malalani Ward", "Kanyangi Ward", "Voo/Kyamu Ward"],
        "Kitui Rural": ["Mutitu Ward", "Kitui South Ward", "Kanyangi Ward", "" /* Add other wards */],
        "Kitui South": ["Mutomo/Kibwezi Ward", "Mutha Ward", "Ikanga/Kyatune Ward", "Kyuu Ward"],
        "Kitui West": ["Mutongoni Ward", "Wamunyu Ward", "Kauwi Ward", "Kyangwithya West Ward"],
        "Lower Yatta": ["Yatta/Kwa Vonza Ward", "Kanziku Ward", "" /* Add other wards */],
        "Matiyani": ["Matiyani Ward", "Kyangwithya West Ward", "Museve Ward"],
        "Migwani": ["Migwani Ward", "Ngaaie Ward", "Thitani Ward", "Kamuloko Ward"],
        "Mutitu": ["Mutitu Ward", "Kwa Mutonga/Kithumula Ward", "Waita Ward"],
        "Mutomo": ["Mutomo/Kibwezi Ward", "Mutha Ward", "Ikanga/Kyatune Ward", "Kyuu Ward"],
        "Muumonikyusu": ["Muumonikyusu Ward", "Nzambani Ward", "Waita Ward"],
        "Mwingi Central": ["Kyome/Thaana Ward", "Mwingi Central Ward", "Nguni Ward", "Nuuni Ward"],
        "Mwingi North": ["Tseikuru Ward", "Mwingi North Ward", "Kyuso Ward", "" /* Add other wards */],
        "Mwingi West": ["Migwani Ward", "Thitani Ward", "Kalyambeu Ward", "Ngaie Ward"],
        "Nzambani": ["Nzambani Ward", "Kitui East Ward", "Mui Ward"],
        "Tseikuru": ["Tseikuru Ward", "Mwingi North Ward", "Ngomeni Ward"]
    },
    "Kwale": {
        "Kinango": ["Mwavumbo Ward", "Kinango Ward", "Puma Ward", "Ndavaya Ward"],
        "Lungalunga": ["Pongwe/Kikoneni Ward", "Mwereni Ward", "Dzombo Ward", "Lungalunga Ward"],
        "Msambweni": ["Msambweni Ward", "Gombato/Bongwe Ward", "Ramisi Ward", "Kinondo Ward"],
        "Matuga": ["Tiwi Ward", "Waa/Ng'ombeni Ward", "Kubo South Ward", "Kubo North Ward"]
    },
    "Laikipia": {
        "Laikipia Central": ["Nanyuki Ward", "Thingithu Ward", "Umande Ward", "Marmanet Ward"],
        "Laikipia East": ["Ngobit Ward", "Tigithi Ward", "Mukogodo East Ward", "Mukogodo West Ward"],
        "Laikipia North": ["Sosian Ward", "Sebi Ward", "Ilmotiok Ward", "Segera Ward"],
        "Laikipia West": ["Rumuruti Ward", "Ol-Moran Ward", "Maji Mazuri Ward", "Salama Ward"],
        "Nyahururu": ["Nyahururu Ward", "Ol Joro Orok Ward", "Rumuruti Ward"]
    },
    "Lamu": {
        "Lamu East": ["Faza Ward", "Pate Ward", "Kizingitini Ward"],
        "Lamu West": ["Shella Ward", "Mkomani Ward", "Hindi Ward", "Witu Ward", "Bahari Ward"]
    },
    "Machakos": {
        "Kathiani": ["Kathiani Ward", "Lower Kaewa/Mavoko Ward", "Kaewa/Mavoko Ward", "" /* Add other wards */],
        "Machakos Town": ["Kalama Ward", "Mumbuni North Ward", "Mumbuni South Ward", "Machakos Central Ward", "Mutituni Ward", "Kola Ward"],
        "Masinga": ["Masinga Central Ward", "Masinga North Ward", "Kithyoko Ward", "Kivaa Ward"],
        "Matungulu": ["Matungulu North Ward", "Matungulu East Ward", "Matungulu West Ward", "Tala Ward"],
        "Mavoko": ["Athi River Ward", "Kinanie Ward", "Syokimau/Mlolongo Ward", "Mlolongo Ward", "" /* Add other wards */],
        "Mwala": ["Mwala Ward", "Wamunyu Ward", "Makutano/Mitaboni Ward", "Masii Ward"],
        "Yatta": ["Yatta/Kithimani Ward", "Katangi Ward", "Nolwe Ward", "Kivandini Ward"]
    },
    "Makueni": {
        "Kaiti": ["Ukia Ward", "Kee Ward", "Ivingoni/Nzambani Ward", "" /* Add other wards */],
        "Kibwezi East": ["Makindu Ward", "Kibwezi West Ward", "Nguumo Ward", "" /* Add other wards */],
        "Kibwezi West": ["Chyulu Ward", "Nzaui/Kilili/Kalamba Ward", "Masongaleni Ward", "" /* Add other wards */],
        "Kilome": ["Ukia Ward", "Kiambu Ward", "Ivingoni/Nzambani Ward"],
        "Makueni": ["Wote Ward", "Wamunyu Ward", "Kako/Waita Ward", "" /* Add other wards */],
        "Mbooni": ["Mbooni Ward", "Tulimani Ward", "Kisau/Kiteta Ward", "Kithungo/Kitundu Ward"]
    },
    "Mandera": {
        "Banissa": ["Banissa Ward", "Guba Ward", "Sarman Ward", "Derkhale Ward"],
        "Lafey": ["Lafey Ward", "Fino Ward", "Warankara Ward", "Arohle Ward"],
        "Mandera East": ["Mandera North Ward", "Mandera West Ward", "Mandera East Ward", "Township Ward", "" /* Add other wards */],
        "Mandera North": ["Rhamu Ward", "Ashabito Ward", "Guticha Ward", "Garbitulla Ward"],
        "Mandera South": ["Shimbir Fatuma Ward", "Lafey Ward", "Elwak South Ward", "" /* Add other wards */],
        "Mandera West": ["Takaba North Ward", "Takaba South Ward", "Dandu Ward", "" /* Add other wards */]
    },
    "Marsabit": {
        "Laisamis": ["Laisamis Ward", "Kargi/Korr/Ngurnit Ward", "Logologo Ward", "" /* Add other wards */],
        "Moyale": ["Moyale Town Ward", "Golbo Ward", "Butiye Ward", "Sololo Ward"],
        "North Horr": ["North Horr Ward", "Maikona Ward", "Turbi Ward", "Dukana Ward"],
        "Saku": ["Saku Ward", "Sagante/Jaldesa Ward", "Karare Ward"]
    },
    "Meru": {
        "Buuri": ["Buuri West Ward", "Buuri East Ward", "Kiguchwa Ward", "Ruiri/Rwarera Ward", "Ntima East Ward", "" /* Add other wards */],
        "Igembe Central": ["Igembe Central Ward", "Akachiu Ward", "Kiegoi/Antuambui Ward", "Athiru Gaiti Ward"],
        "Igembe North": ["Igembe North Ward", "Laare Ward", "Amwathi Ward", "Antuambui Ward"],
        "Igembe South": ["Maua Ward", "Kiegoi/Antuambui Ward", "Athiru Gaiti Ward", "Akachiu Ward"],
        "Imenti Central": ["Abogeta East Ward", "Abogeta West Ward", "Kigumo Ward", "Mitunguu Ward"],
        "Imenti North": ["Municipality Ward", "Imenti North Ward", "Abothuguchi Central Ward", "Abothuguchi West Ward"],
        "Imenti South": ["Imenti South Ward", "Nkuene Ward", "Mwangathia Ward", "Kanyakine Ward", "" /* Add other wards */],
        "Tigania East": ["Kiguchwa Ward", "Mikinduri Ward", "Muthara Ward", "Karama Ward"],
        "Tigania West": ["Athwana Ward", "Akithi Ward", "Kianjai Ward", "Mbeu Ward"]
    },
    "Migori": {
        "Awendo": ["Awendo Ward", "North Sakwa Ward", "South Sakwa Ward", "God Jope Ward"],
        "Kuria East": ["Nyabasi East Ward", "Nyabasi West Ward", "Ntimaru East Ward", "Ntimaru West Ward"],
        "Kuria West": ["Masaba Ward", "Tagare Ward", "Makerero Ward", "Gokeharaka/Getenga Ward"],
        "Nyatike": ["Nyatike East Ward", "Nyatike West Ward", "Kanyamkago Ward", "" /* Add other wards */],
        "Rongo": ["Rongo Ward", "North Kamagambo Ward", "South Kamagambo Ward", "East Kamagambo Ward"],
        "Suna East": ["Kakrao Ward", "East Suna Ward", "Komolo Rume Ward", "" /* Add other wards */],
        "Suna West": ["Wasweta II Ward", "Ragana/Ombo Ward", "Kaler Ward", "" /* Add other wards */],
        "Uriri": ["Uriri Ward", "North Kanyamkago Ward", "South Kanyamkago Ward", "Central Kanyamkago Ward"]
    },
    "Mombasa": {
        "Changamwe": ["Chaani Ward", "Kipevu Ward", "Miritini Ward", "Port Reitz Ward", "Changamwe Ward"],
        "Jomvu": ["Jomvu Kuu Ward", "Mikindani Ward", "Miritini Ward"],
        "Kisauni": ["Bamburi Ward", "Mtopanga Ward", "Mwandoni Ward", "Shanzu Ward", "Kadongo Ward", "" /* Add other wards */],
        "Likoni": ["Bofu Ward", "Likoni Ward", "Mtongwe Ward", "Shika Adabu Ward", "Timbwani Ward"],
        "Mvita": ["Majengo Ward", "Mji Wa Kale/Old Town Ward", "Shimanzi/Ganjoni Ward", "Tononoka Ward", "Mvita Ward"],
        "Nyali": ["Frere Town Ward", "Kadzonzo Ward", "Kisauni Ward", "Kongowea Ward", "Nyali Ward"]
    },
    "Murang'a": {
        "Gatanga": ["Gatanga Ward", "Gatara Ward", "Kibugi Ward", "Mangu Ward", "Mugumo-ini Ward"],
        "Kandara": ["Kandara Ward", "Githunguri Ward", "Ng'araria Ward", "Kihumbu-ini Ward", "Muruka Ward"],
        "Kangema": ["Kanyenya-ini Ward", "Muguru Ward", "Rwathia Ward"],
        "Kigumo": ["Kigumo Ward", "Kahumbu Ward", "Kangari Ward", "Kanyenya-ini Ward", "" /* Add other wards */],
        "Kiharu": ["Kiharu Ward", "Mugoiri Ward", "Wangu Ward", "Kahuro Ward", "Munyoro Ward"],
        "Maragua": ["Maragua Ridge Ward", "Mikalai Ward", "Ichagaki Ward", "Kambiti Ward"],
        "Mathioya": ["Gatara Ward", "Kiriti Ward", "Kiru Ward", "Njumbi Ward"],
        "Murang'a South": ["Makuyu Ward", "Kambiti Ward", "Kandara Ward", "" /* Add other wards */]
    },
    "Nairobi": {
        "Dagoretti North": ["Kileleshwa Ward", "Kawangware Ward", "Kilimani Ward", "Lavington Ward", "Gatina Ward"],
        "Dagoretti South": ["Mutuini Ward", "Ngand'o Ward", "Waithaka Ward", "Riruta Ward"],
        "Embakasi Central": ["Kariobangi South Ward", "Dandora Phase I Ward", "Dandora Phase II Ward", "Dandora Phase III Ward", "Dandora Phase IV Ward"],
        "Embakasi East": ["Embakasi Ward", "Lower Savannah Ward", "Upper Savannah Ward", "Umoja I Ward", "Umoja II Ward"],
        "Embakasi North": ["Kariobangi North Ward", "Dandora Area I Ward", "Dandora Area II Ward", "Dandora Area III Ward", "Dandora Area IV Ward"],
        "Embakasi South": ["Imara Daima Ward", "Kware Ward", "Mihango Ward", "Pipeline Ward"],
        "Embakasi West": ["Umoja Ward", "Mihango Ward", "Kware Ward", "Embakasi West Ward"],
        "Kamukunji": ["Eastleigh North Ward", "Eastleigh South Ward", "Pumwani Ward", "Airbase Ward", "Biashara Ward"],
        "Kasarani": ["Kasarani Ward", "Mwiki Ward", "Clay City Ward", "Roysambu Ward", "Ruaraka Ward"],
        "Kibra": ["Laini Saba Ward", "Lindi Ward", "Makina Ward", "Sarang'ombe Ward", "Woodley/Kenyatta Golf Course Ward"],
        "Lang'ata": ["Karen Ward", "Mugumo-ini Ward", "South C Ward", "Nairobi West Ward", "Lang'ata Ward"],
        "Makadara": ["Makongeni Ward", "Kaloleni/Mbotela Ward", "Hamza Ward", "Maringo/Hamza Ward", "Viwandani Ward"],
        "Mathare": ["Huruma Ward", "Mabatini Ward", "Mathare North Ward", "Ngei Ward", "" /* Add other wards */],
        "Roysambu": ["Roysambu Ward", "Githurai Ward", "Kahawa West Ward", "Kahawa North Ward", "Zimmerman Ward"],
        "Ruaraka": ["Baba Dogo Ward", "Korogocho Ward", "Mathare North Ward", "Dandora Ward", "" /* Add other wards */],
        "Starehe": ["Central Ward", "Ngara Ward", "Pangani Ward", "Landimawe Ward", "Ziwani/Kariokor Ward"],
        "Westlands": ["Kitisuru Ward", "Parklands/Highridge Ward", "Karura Ward", "Kangemi Ward", "Mountain View Ward", "Waiyaki Way Ward"]
    },
    "Nakuru": {
        "Bahati": ["Bahati Ward", "Dundori Ward", "Kabatini Ward", "Kiamaina Ward"],
        "Gilgil": ["Gilgil Ward", "Elementaita Ward", "Malewa West Ward", "Murindat Ward"],
        "Kuresoi North": ["Kipkelion Ward", "Kuresoi North Ward", "Amalo Ward", "Keringet Ward"],
        "Kuresoi South": ["Keringet Ward", "Amalo Ward", "Tinet Ward", "Kuresoi South Ward"],
        "Naivasha": ["Biashara Ward", "Hells Gate Ward", "Lakeview Ward", "Maimahiu Ward", "Naivasha East Ward", "Naivasha West Ward", "Viwandani Ward"],
        "Nakuru Town East": ["Biashara Ward", "Flamingo Ward", "Kivumbini Ward", "Lake View Ward", "Shabab Ward"],
        "Nakuru Town West": ["Barut Ward", "London Ward", "Shauri Yako Ward", "Kapkures Ward"],
        "Njoro": ["Njoro Ward", "Mauche Ward", "Lare Ward", "Kihingo Ward"],
        "Rongai": ["Rongai Ward", "Solai Ward", "Visoi Ward", "Kampi Ya Moto Ward"],
        "Subukia": ["Subukia Ward", "Waseges Ward", "Kabazi Ward"]
    },
    "Nandi": {
        "Aldai": ["Kaptumo/Kaboi Ward", "Koyochim Ward", "Terik Ward", "Ndonyiro Ward"],
        "Chesumei": ["Chemundu/Sangalo Ward", "Kapsabet Ward", "Kosirai Ward", "Kiptuya Ward"],
        "Emgwen": ["Kapsabet/Kamwega Ward", "Kilibwoni Ward", "Kapkanga Ward", "Kigumo Ward"],
        "Mosop": ["Kurgung/Surungai Ward", "Chemundu/Sangalo Ward", "Kabiyet Ward", "Kurgung Ward", "Sarupio Ward"],
        "Nandi Hills": ["Nandi Hills Ward", "Chepkumia Ward", "Kapsimotwo Ward", "Tindiret Ward"],
        "Tindiret": ["Tindiret Ward", "Meteitei Ward", "Chemase Ward", "Kapsimotwo Ward"]
    },
    "Narok": {
        "Kilgoris": ["Kilgoris Central Ward", "Keyian Ward", "Angata Barikoi Ward", "Shankoe Ward"],
        "Narok East": ["Ololulunga Ward", "Maji Moto Ward", "Naikarra Ward", "" /* Add other wards */],
        "Narok North": ["Narok Town Ward", "Olopito Ward", "Nkareta Ward", "Olorropil Ward"],
        "Narok South": ["Loita Ward", "Maji Moto Ward", "Sogoo Ward", "Melelo Ward"],
        "Narok West": ["Loita Ward", "Siana Ward", "Lemek Ward", "Nkareta Ward", "Olesholey Ward"],
        "Emurua Dikirr": ["Ilkerin Ward", "Ololulunga Ward", "Angata Barikoi Ward"]
    },
    "Nyamira": {
        "Borabu": ["Rangenyo Ward", "Nyansiongo Ward", "Borabu Ward", "Manga Ward"],
        "Masaba North": ["Masaba North Ward", "Kegogi Ward", "Bokeira Ward", "Mugirango West Ward"],
        " Missing Nyamira North " : ["Township Ward", "Bogichora Ward", "Bokeira Ward", "Nyankuru Ward"], // Placeholder
        "Nyamira South": ["Bokimonge Ward", "Bonyamatuta Ward", "Kebirigo Ward", "Nyamira Township Ward"],
        "Rigoma": ["Rigoma Ward", "Gesiaga Ward", "Nyabite Ward", "Bosamaro Ward"],
        "West Mugirango": ["Nyabite Ward", "Getare Ward", "Rigoma Ward", "Ekerenyo Ward"]
    },
    "Nyandarua": {
        "Kinangop": ["Githabai Ward", "Kinangop Central Ward", "Kinangop North Ward", "Kinangop South Ward", "Njabini/Kiburu Ward"],
        "Kipipiri": ["Geta Ward", "Kipipiri Ward", "Wanjohi Ward", "Githioro Ward"],
        "Ndaragwa": ["Ndaragwa Central Ward", "Leshau/Pondo Ward", "Shamata Ward", "Dundori Ward"],
        "Ol Kalou": ["Ol Kalou Town Ward", "Githunguri Ward", "Karau Ward", "Rurii Ward", "Kaimbaga Ward"],
        "Ol Joro Orok": ["Ol Joro Orok Ward", "Gatimu Ward", "Murungaru Ward", "Passenga Ward"]
    },
    "Nyeri": {
        "Kieni East": ["Mweiga Ward", "Naromoru/Kiamariga Ward", "Mwiyogo/Endarasha Ward", "Amboni/Kanyurira Ward"],
        "Kieni West": ["Mukurweini Central Ward", "Mukurweini West Ward", "Gikondi Ward", "Muhito Ward"],
        "Mathira East": ["Kirimukuyu Ward", "Ruguru Ward", "Mutira Ward", "Konyu Ward"],
        "Mathira West": ["Magutu Ward", "Mukurweini Central Ward", "Ngorano Ward", "" /* Add other wards */],
        "Mukurweini": ["Gikondi Ward", "Muhito Ward", "Mukurweini Central Ward", "Mukurweini West Ward"],
        "Nyeri Town": ["Rware Ward", "Kamakwa/Mukaro Ward", "Gatitu/Muruguru Ward", "" /* Add other wards */],
        "Othaya": ["Mahiga Ward", "Iyego Ward", "Chinga Ward", "Othaya Ward"],
        "Tetu": ["Aguthi/Gaaki Ward", "Kiganjo/Mathira Ward", "Karundu/Mugunda Ward", "Muthuaini Ward"]
    },
    "Samburu": {
        "Samburu East": ["Wamba East Ward", "Wamba West Ward", "Wamba North Ward", "Lodokejek Ward"],
        "Samburu North": ["Baragoi Ward", "Nachola Ward", "Ndoto Ward", "Nyiro Ward"],
        "Samburu West": ["Lodokejek Ward", "Maralal Old Town Ward", "Baawa Ward", "Loosuk Ward", "Poron Ward"]
    },
    "Siaya": {
        "Alego Usonga": ["Siaya Township Ward", "Alego Usonga Ward", "Usonga Ward", "Karemo Ward", "" /* Add other wards */],
        "Bondo": ["Bondo Town Ward", "Usigu Ward", "Nyatike Ward", "South Sakwa Ward"],
        "Gem": ["North Gem Ward", "Central Gem Ward", "South Gem Ward", "Yala Township Ward"],
        "Rarieda": ["East Asembo Ward", "West Asembo Ward", "Asembo Central Ward", "South East Rarieda Ward"],
        "Ugenya": ["East Ugenya Ward", "North Ugenya Ward", "West Ugenya Ward", "Ugenya Ward"],
        "Ugunja": ["Ugunja Ward", "Sidindi Ward", "Ambira/Murambo/Ukhoh Ward"]
    },
    "Taita-Taveta": {
        "Mwatate": ["Mwatate Ward", "Bura Ward", "Wumingu/Bura Ward", "Ronge Ward"],
        "Taveta": ["Taveta Ward", "Mata Ward", "Chala Ward", "Mboghoni Ward"],
        "Voi": ["Voi Ward", "Sagalla Ward", "Mbololo Ward", "Kasigau Ward", "Marungu Ward"],
        "Wundanyi": ["Wundanyi/Mbale Ward", "Wumingu/Kishushe Ward", "Werugha Ward", "Mgange/Mwanda Ward"]
    },
    "Tana River": {
        "Bura": ["Chewele/Mikinduni Ward", "Bangale Ward", "Sala Ward", "Madogo Ward"],
        "Galole": ["Garsen South Ward", "Garsen Central Ward", "Chewele Ward", "Mikinduni Ward"],
        "Garsen": ["Garsen Central Ward", "Garsen South Ward", "Kipini East Ward", "Kipini West Ward"]
    },
    "Tharaka-Nithi": {
        "Chuka Igambang'ombe": ["Karingani Ward", "Magumoni Ward", "Muthambi Ward", "Chuka Ward"],
        "Maara": ["Chogoria Ward", "Mitheru Ward", "Mwimbi Ward", "Gatunga Ward"],
        "Tharaka Nithi": ["Tharaka Ward", "Mutino Ward", "Chiakariga Ward", "Marimanti Ward"]
    },
    "Trans-Nzoia": {
        "Cherangany": ["Motosiet Ward", "Sinyerere Ward", "Kaplamai Ward", "Sitatunga Ward"],
        "Endebess": ["Endebess Ward", "Kwanza Ward", "Kinyoro Ward", "Saboti Ward"],
        "Kiminini": ["Waitaluk Ward", "Kiminini Ward", "Saboti Ward", "" /* Add other wards */],
        "Kwanza": ["Kwanza Ward", "Kiminini Ward", "Endebess Ward", "Chepsiro/Kiptoror Ward"],
        "Saboti": ["Saboti Ward", "Matisi Ward", "Tuwan Ward", "Kinyoro Ward"]
    },
    "Turkana": {
        "Turkana Central": ["Lodwar Town Ward", "Loima Ward", "Turkwel Ward", "Kerio Delta Ward"],
        "Turkana East": ["Kapedo/Napei Ward", "Kapedo/Napei Ward", "Lokori/Kochodin Ward", "" /* Add other wards */],
        "Turkana North": ["Lokitaung Ward", "Lokichoggio Ward", "Kibish Ward", "" /* Add other wards */],
        "Turkana South": ["Kalokol Ward", "Katilu Ward", "Lokichar Ward", "" /* Add other wards */],
        "Turkana West": ["Turkana West Ward", "Loima Ward", "Kakuma Ward", "Lopur Ward"],
        "Loima": ["Loima Ward", "Kotaruk/Lobei Ward", "Turkwel Ward"]
    },
    "Uasin Gishu": {
        "Ainabkoi": ["Ainabkoi/Olare Ward", "Kaptagat Ward", "Kapsoya Ward", "Plateau Ward"],
        "Kapseret": ["Kapseret/Simat Ward", "Cheptiret/Kipchamo Ward", "Racecourse Ward", "Langas Ward"],
        "Kesses": ["Kesses Ward", "Tarakwa Ward", "Cheptiret/Kipchamo Ward", "" /* Add other wards */],
        "Moiben": ["Moiben Ward", "Tembely/Chemase Ward", "Sergoit Ward", "Chebarus Ward"],
        "Soy": ["Soy Ward", "Ziwa Ward", "Kiplombe Ward", "Kapkures Ward"],
        "Turbo": ["Turbo Ward", "Kamagut Ward", "Tapsagoi Ward", "Kipsomba Ward"]
    },
    "Vihiga": {
        "Emuhaya": ["Central Bunyore Ward", "West Bunyore Ward", "East Bunyore Ward", "Itumbi/Central Bunyore Ward"],
        "Luanda": ["Luanda Town Ward", "Emabungo Ward", "Wamuluma Ward", "Mwibona Ward"],
        "Sabatia": ["Chavakali Ward", "Izava/Lyaduywa Ward", "North Maragoli Ward", "Sabatia Ward", "" /* Add other wards */],
        "Vihiga": ["Central Maragoli Ward", "North Maragoli Ward", "South Maragoli Ward", "" /* Add other wards */],
        "Hamisi": ["Hamisi Ward", "Jepkoyai Ward", "Gamoi Ward", "Shirugu/Mugai Ward"]
    },
    "Wajir": {
        "Eldas": ["Eldas Ward", "Boru Ward", "" /* Add other wards */],
        "Habaswein": ["Habaswein Ward", "Abakore Ward", "Lagbogol Ward", "" /* Add other wards */],
        "Lafey": ["Lafey Ward", "Fino Ward", "Warankara Ward", "" /* Add other wards */],
        "Mdira": ["Mdira Ward", "Sarman Ward", "" /* Add other wards */], // Note: Mdira might be a location, not a full subcounty. Data verification needed.
        "Tarbaj": ["Tarbaj Ward", "Sarman Ward", "" /* Add other wards */],
        "Wajir East": ["Wajir East Ward", "Township Ward", "Hospital Ward", "" /* Add other wards */],
        "Wajir North": ["Bute Ward", "Korondile Ward", "Danaba Ward", "" /* Add other wards */],
        "Wajir South": ["Lagbogol Ward", "Benane Ward", "Diff Ward", "" /* Add other wards */],
        "Wajir West": ["Arbajahan Ward", "Ganyure Ward", "" /* Add other wards */]
    },
    "West Pokot": {
        "Kapenguria": ["Kapenguria Ward", "Mnagei Ward", "Riwo Ward", "" /* Add other wards */],
        "Kacheliba": ["Kacheliba Ward", "Suam Ward", "Kasei Ward", "" /* Add other wards */],
        "Pokot South": ["Sook Ward", "Wei Wei Ward", "" /* Add other wards */],
        "Sigor": ["Sigor Ward", "Cheptulel Ward", "Sekerr Ward", "" /* Add other wards */]
    }
};

function setupCountyDropdown(countyId, subCountyId, wardId, selectedCounty, selectedSub, selectedWard) {
  const countySelect = document.getElementById(countyId);
  const subCountySelect = document.getElementById(subCountyId);
  const wardSelect = document.getElementById(wardId);

  Object.keys(countyData).forEach(county => {
    const option = document.createElement("option");
    option.value = county;
    option.textContent = county;
    countySelect.appendChild(option);
  });

  if (selectedCounty) {
    countySelect.value = selectedCounty;
    populateSubCounties(selectedCounty, subCountySelect, wardSelect, selectedSub, selectedWard);
  }

  countySelect.addEventListener("change", function () {
    populateSubCounties(this.value, subCountySelect, wardSelect);
  });
}

function populateSubCounties(selectedCounty, subCountySelect, wardSelect, selectedSub, selectedWard) {
  subCountySelect.innerHTML = '<option value="">Select Sub-County</option>';
  wardSelect.innerHTML = '<option value="">Select Ward</option>';

  if (selectedCounty && countyData[selectedCounty]) {
    Object.keys(countyData[selectedCounty]).forEach(subCounty => {
      const option = document.createElement("option");
      option.value = subCounty;
      option.textContent = subCounty;
      subCountySelect.appendChild(option);
    });
    if (selectedSub) {
      subCountySelect.value = selectedSub;
      populateWards(selectedCounty, selectedSub, wardSelect, selectedWard);
    }
  }

  subCountySelect.addEventListener("change", function () {
    populateWards(selectedCounty, this.value, wardSelect);
  });
}

function populateWards(county, subCounty, wardSelect, selectedWard) {
  wardSelect.innerHTML = '<option value="">Select Ward</option>';
  if (county && subCounty && countyData[county]?.[subCounty]) {
    countyData[county][subCounty].forEach(ward => {
      const option = document.createElement("option");
      option.value = ward;
      option.textContent = ward;
      wardSelect.appendChild(option);
    });
    if (selectedWard) wardSelect.value = selectedWard;
  }
}

// Initialize both sections with preselected data
setupCountyDropdown("county", "sub_county", "ward",
  "<?= $existingData['county'] ?? '' ?>",
  "<?= $existingData['sub_county'] ?? '' ?>",
  "<?= $existingData['ward'] ?? '' ?>"
);
setupCountyDropdown("home_county", "home_sub_county", "home_ward",
  "<?= $existingData['home_county'] ?? '' ?>",
  "<?= $existingData['home_sub_county'] ?? '' ?>",
  "<?= $existingData['home_ward'] ?? '' ?>"
);
</script>
</body>
</html>
