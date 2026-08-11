document.addEventListener("DOMContentLoaded", () => {

    /* Sticky Navbar */
    const navbar = document.querySelector(".navbar");

    window.addEventListener("scroll", () => {

        if (window.scrollY > 80) {

            navbar.classList.add("sticky");

        } else {

            navbar.classList.remove("sticky");

        }

    });

    /* Animated Counters */
    const counters = document.querySelectorAll(".counter");

    counters.forEach(counter => {

        const update = () => {

            const target = +counter.dataset.target;

            const current = +counter.innerText;

            const increment = Math.ceil(target / 100);

            if (current < target) {

                counter.innerText = current + increment;

                setTimeout(update, 20);

            } else {

                counter.innerText = target;

            }

        };

        update();

    });

    /* Fade In Sections */
    const observer = new IntersectionObserver(entries => {

        entries.forEach(entry => {

            if (entry.isIntersecting) {

                entry.target.classList.add("show");

            }

        });

    }, {
        threshold: 0.2
    });

    document.querySelectorAll("section").forEach(section => {

        section.classList.add("hidden");

        observer.observe(section);

    });

});