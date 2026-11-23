
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | SK KONEXT</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;700&family=Plus+Jakarta+Sans:wght@200;400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #0b1711;
            --panel: #12251a;
            --green: #3b8055;
            --green-light: #78b68b;
            --border: #263e2e;
            --text: #edf4ee;
            --muted: #91a398;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
        }

        .grain {
            position: fixed;
            inset: 0;
            background: url('https://grainy-gradients.vercel.app/noise.svg');
            opacity: 0.035;
            pointer-events: none;
            z-index: 999;
        }

        .outline-text {
            font-family: 'Space Grotesk', sans-serif;
            -webkit-text-stroke: 1px rgba(120, 182, 139, 0.10);
            color: transparent;
            font-size: 15vw;
            white-space: nowrap;
            position: absolute;
            top: 20%;
            z-index: -1;
            pointer-events: none;
        }

        .team-img {
            filter: grayscale(100%);
            transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .team-card:hover .team-img {
            filter: grayscale(0%);
            transform: scale(1.05);
        }

        .member-info {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
        }

        .team-card:hover .member-info {
            opacity: 1;
            transform: translateY(0);
        }

        .green-text {
            color: var(--green-light);
        }

        .nav-link {
            transition: .3s;
        }

        .nav-link:hover {
            color: var(--green-light);
        }

        .team-card {
            cursor: default;
        }

        .team-image-wrapper {
            background: #182e21;
            border: 1px solid var(--border);
        }

        .cta-button {
            background: var(--green-light);
            color: #0b1711;
            transition: all .4s ease;
        }

        .cta-button:hover {
            background: #3b8055;
            color: white;
            transform: translateY(-3px);
        }

        @media(max-width: 768px) {
            .outline-text {
                font-size: 22vw;
                top: 18%;
            }

            nav {
                padding-left: 22px !important;
                padding-right: 22px !important;
            }

            .hero-section {
                padding-left: 24px !important;
                padding-right: 24px !important;
            }
        }
    </style>
</head>

<body>

    <div class="grain"></div>

    <div class="outline-text" id="parallax-text">
        SK KONEXT — SK KONEXT — SK KONEXT
    </div>

    <!-- NAVIGATION -->
    <nav class="fixed top-0 left-0 w-full z-[1000] flex justify-between items-center px-10 py-8 bg-[#0b1711]/80 backdrop-blur-md">

        <a href="index.php" class="text-xl font-bold tracking-tighter">
            SK<span class="green-text">KONEXT</span>
        </a>

        <div class="flex gap-8 text-xs font-semibold uppercase tracking-widest text-gray-400">
            <a href="index.php" class="index.php">Home</a>
            
        </div>

    </nav>

    <main class="relative pt-40">

        <!-- HERO / IDENTITY -->
        <section class="hero-section px-10 mb-32">

            <h2 class="green-text font-mono text-sm tracking-[0.5em] mb-4 uppercase">
                // Our Identity
            </h2>

            <h1 class="text-5xl md:text-8xl font-bold tracking-tighter leading-none"
                style="font-family: 'Space Grotesk';">

                The minds behind <br>
                the <span class="italic font-light green-text">machine.</span>

            </h1>

            <p class="text-gray-400 text-sm md:text-base mt-8 max-w-2xl leading-relaxed">
                Meet the people behind the ideas, design, and development
                of SK KONEXT. A team working together to create meaningful
                digital solutions for the community.
            </p>

        </section>

        <!-- TEAM MEMBERS -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-10 px-6 md:px-10 pb-40">

            <!-- JAKE -->
            <div class="team-card group">

                <div class="relative aspect-[3/4] overflow-hidden rounded-2xl team-image-wrapper">

                    <!-- Replace with your own image -->
                    <img
                        src="images/jake.jpg"
                        alt="Jake Tarrayo"
                        class="team-img w-full h-full object-cover"
                    >

                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent"></div>

                    <div class="absolute bottom-8 left-8 member-info">

                        <p class="green-text font-mono text-xs mb-1">
                            Designer
                        </p>

                        <h3 class="text-2xl font-bold">
                            Jake Tarrayo
                        </h3>

                    </div>

                </div>

                <p class="mt-6 text-gray-400 text-sm leading-relaxed pr-4 italic">
                    "Passionate about creating beautiful, functional websites that push digital boundaries."
                </p>

            </div>

            <!-- ALEXIS -->
            <div class="team-card group md:mt-24">

                <div class="relative aspect-[3/4] overflow-hidden rounded-2xl team-image-wrapper">

                    <!-- Replace with your own image -->
                    <img
                        src="raven.jpg"
                        alt="Rave Gallardo"
                        class="team-img w-full h-full object-cover"
                    >

                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent"></div>

                    <div class="absolute bottom-8 left-8 member-info">

                        <p class="green-text font-mono text-xs mb-1">
                            Critic
                        </p>

                        <h3 class="text-2xl font-bold">
                            Raven Gallardo
                        </h3>

                    </div>

                </div>

                <p class="mt-6 text-gray-400 text-sm leading-relaxed pr-4 italic">
                    "A talent for learning and discovering new ways to weave design into digital reality."
                </p>

            </div>

            <!-- FELIX -->
            <div class="team-card group">

                <div class="relative aspect-[3/4] overflow-hidden rounded-2xl team-image-wrapper">

                    <!-- Replace with your own image -->
                    <img
                        src="images/felix.jpg"
                        alt="Felix Nacional"
                        class="team-img w-full h-full object-cover"
                    >

                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent"></div>

                    <div class="absolute bottom-8 left-8 member-info">

                        <p class="green-text font-mono text-xs mb-1">
                            Strategist
                        </p>

                        <h3 class="text-2xl font-bold">
                            Felix Nacional
                        </h3>

                    </div>

                </div>

                <p class="mt-6 text-gray-400 text-sm leading-relaxed pr-4 italic">
                    "Focused on the documents and structure required to finish goal-oriented projects."
                </p>

            </div>

        </section>

        <!-- CALL TO ACTION -->
        <section class="py-40 text-center px-6 border-t border-[#263e2e]">

            <h2 class="text-3xl md:text-4xl font-bold mb-6 tracking-tight">
                 Thanks to this people!
            </h2>

            <p class="text-gray-400 text-sm mb-10">
                Discover more about SK KONEXT.
            </p>

            

        </section>

    </main>

    <!-- FOOTER -->
    <footer class="border-t border-[#263e2e] py-8 px-6 text-center">

        <p class="text-gray-500 text-xs tracking-widest">
            © <?php echo date("Y"); ?> SK KONEXT. ALL RIGHTS RESERVED.
        </p>

    </footer>

    <!-- ANIMATIONS -->
    <script>
        gsap.registerPlugin(ScrollTrigger);

        gsap.to("#parallax-text", {
            xPercent: -20,
            ease: "none",
            scrollTrigger: {
                trigger: "body",
                start: "top top",
                end: "bottom bottom",
                scrub: 1
            }
        });

        gsap.from(".team-card", {
            opacity: 0,
            y: 100,
            stagger: 0.2,
            duration: 1.5,
            ease: "power4.out",
            scrollTrigger: {
                trigger: ".grid",
                start: "top 80%"
            }
        });
    </script>

</body>
</html>
