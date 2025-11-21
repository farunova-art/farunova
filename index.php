<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- navbar section   -->

    <header class="navbar-section">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand" href="#"><i class="bi bi-shop"></i> FARUNOVA</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="#home">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#services">services</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#about">about us</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#projects">projects</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#contact">contact</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="signup.php">signup</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- hero section  -->

    <section id="home" class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-12 col-sm-12 text-content">
                    <h1>Premium Authentic Clothing</h1>
                    <p>Discover our exclusive collection of high-quality Shirts, Trousers, and Hoodies. Authentic style for every occasion.
                    </p>
                    <button class="btn"><a href="products.php">Shop Now</a></button>
                </div>
                <div class="col-lg-8 col-md-12 col-sm-12">
                    <img src="images/hero-image.png" alt="" class="img-fluid">
                </div>

            </div>
        </div>
    </section>

    <!-- services section  -->

    <section class="services-section" id="services">
        <div class="container">
            <div class="row">

                <div class="col-lg-6 col-md-12 col-sm-12 services">

                    <div class="row row1">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="card">
                                <img src="images/research.png" class="card-img-top" alt="...">
                                <div class="card-body">
                                    <h4 class="card-title">Quality Fabrics</h4>
                                    <p class="card-text">We carefully select premium materials for durability, comfort, and style that lasts.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="card">
                                <img src="images/brand.png" class="card-img-top" alt="...">
                                <div class="card-body">
                                    <h4 class="card-title">Authentic Style</h4>
                                    <p class="card-text">Our designs reflect current trends while maintaining timeless appeal for every wardrobe.</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row row2">

                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="card">
                                <img src="images/ux.png" class="card-img-top" alt="...">
                                <div class="card-body">
                                    <h4 class="card-title">Fast Shipping</h4>
                                    <p class="card-text">Quick delivery across Kenya with tracking. Your orders arrive when you need them.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="card">
                                <img src="images/app-development.png" class="card-img-top" alt="...">
                                <div class="card-body">
                                    <h4 class="card-title">Secure Payments</h4>
                                    <p class="card-text">Safe and secure M-Pesa payment options for your peace of mind when shopping.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="col-lg-6 col-md-12 col-sm-12 text-content">
                    <h3>why choose us</h3>
                    <h1>The Best Selection of Authentic Clothing</h1>
                    <p>At FARUNOVA, we pride ourselves on offering authentic, high-quality clothing at competitive prices. With our secure M-Pesa payment system and fast delivery, shopping has never been easier.</p>
                    <button class="btn"><a href="products.php">Browse Collection</a></button>
                </div>

            </div>
        </div>
    </section>

    <!-- about section  -->

    <section class="about-section" id="about">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-md-12 col-sm-12">
                    <img src="images/about.jpg" alt="" class="img-fluid">
                </div>
                <div class="col-lg-6 col-md-12 col-sm-12 text-content">
                    <h3>about farunova</h3>
                    <h1>Providing Authentic Clothing for Every Occasion</h1>

                    <p>Our company specializes in offering premium Shirts, Trousers, and Hoodies with guaranteed authenticity. To help our customers look and feel their best, we work with quality suppliers and offer competitive pricing worldwide.</p>
                    <button><a href="products.php">Learn More</a></button>
                </div>
            </div>
        </div>
    </section>

    <!-- project section  -->

    <section class="project-section" id="projects">
        <div class="container">
            <div class="row text">
                <div class="col-lg-6 col-md-12">
                    <h3>featured</h3>
                    <h1>Our Latest Collections</h1>
                    <hr>
                </div>
                <div class="col-lg-6 col-md-12">
                    <p>We bring product close to our heart. We showcase your style to reality and make your fashion dream successful with awesome quality.</p>
                </div>
            </div>
            <div class="row project">

                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="card">
                        <img src="images/project1.jpg" class="card-img-top" alt="...">
                        <div class="card-body">
                            <div class="text">
                                <h4 class="card-title">Premium Shirts</h4>
                                <p class="card-text">Collection. Latest</p>
                                <button>shop now</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="card">
                        <img src="images/project2.jpg" class="card-img-top" alt="...">
                        <div class="card-body">
                            <div class="text">
                                <h4 class="card-title">Classic Trousers</h4>
                                <p class="card-text">Collection. Best Sellers</p>
                                <button>shop now</button>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="card">
                        <img src="images/project3.jpg" class="card-img-top" alt="...">
                        <div class="card-body">
                            <div class="text">
                                <h4 class="card-title">Cozy Hoodies</h4>
                                <p class="card-text">Collection. New Arrival</p>
                                <button>shop now</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="card">
                        <img src="images/project4.jpg" class="card-img-top" alt="...">
                        <div class="card-body">
                            <div class="text">
                                <h4 class="card-title">Seasonal Sale</h4>
                                <p class="card-text">Collection. Limited Offer</p>
                                <button>shop now</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- contact section  -->

    <section class="contact-section" id="contact">
        <div class="container">

            <div class="row gy-4">

                <h1>contact us</h1>
                <div class="col-lg-6">

                    <div class="row gy-4">
                        <div class="col-md-6">
                            <div class="info-box">
                                <i class="bi bi-geo-alt"></i>
                                <h3>Address</h3>
                                <p>Nairobi,<br>Kenya</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <i class="bi bi-telephone"></i>
                                <h3>Call Us</h3>
                                <p>+254 712 345678<br>+254 701 234567</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <i class="bi bi-envelope"></i>
                                <h3>Email Us</h3>
                                <p>support@farunova.com<br>info@farunova.com</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <i class="bi bi-clock"></i>
                                <h3>Open Hours</h3>
                                <p>Monday - Friday<br>9:00AM - 05:00PM</p>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="col-lg-6 form">
                    <form action="contact.php" method="POST" class="php-email-form">
                        <div class="row gy-4">

                            <div class="col-md-6">
                                <input type="text" name="name" class="form-control" placeholder="Your Name" required>
                            </div>

                            <div class="col-md-6 ">
                                <input type="email" class="form-control" name="email" placeholder="Your Email" required>
                            </div>

                            <div class="col-md-12">
                                <input type="text" class="form-control" name="subject" placeholder="Subject" required>
                            </div>

                            <div class="col-md-12">
                                <textarea class="form-control" name="message" rows="5" placeholder="Message"
                                    required></textarea>
                            </div>

                            <div class="col-md-12 text-center">
                                <button type="submit" name="submit">Send Message</button>
                            </div>

                        </div>
                    </form>

                </div>

            </div>

        </div>
    </section>

    <!-- footer section  -->

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-12 col-sm-12">
                    <p class="logo"><i class="bi bi-shop"></i> FARUNOVA</p>
                </div>
                <div class="col-lg-6 col-md-12 col-sm-12">
                    <ul class="d-flex">
                        <li><a href="#">Home</a></li>
                        <li><a href="#">services</a></li>
                        <li><a href="#">projects</a></li>
                        <li><a href="#">about us</a></li>
                        <li><a href="#">contact</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-12 col-sm-12">
                    <p>&copy;2025_FARUNOVA - Authentic Clothing Store</p>
                </div>

                <div class="col-lg-1 col-md-12 col-sm-12">
                    <!-- back to top  -->

                    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
                            class="bi bi-arrow-up-short"></i></a>
                </div>

            </div>

        </div>

    </footer>




    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz"
        crossorigin="anonymous"></script>
</body>

</html>