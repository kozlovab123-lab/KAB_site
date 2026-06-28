(function () {
  const HOME = "/";

  function goBack() {
    if (window.history.length > 1) {
      window.history.back();
      return;
    }
    window.location.href = HOME;
  }

  document.documentElement.classList.add("kab-demo-page");
  document.body.classList.add("kab-demo-page");

  const bar = document.createElement("div");
  bar.className = "kab-back-bar";
  bar.innerHTML =
    '<a href="' +
    HOME +
    '" class="kab-back-bar__link">← На главную KAB</a>' +
    '<button type="button" class="kab-back-bar__close" aria-label="Закрыть образец">✕ Закрыть</button>';

  document.body.prepend(bar);

  bar.querySelector(".kab-back-bar__link").addEventListener("click", function (e) {
    e.preventDefault();
    window.location.href = HOME;
  });

  bar.querySelector(".kab-back-bar__close").addEventListener("click", goBack);

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      goBack();
    }
  });
})();
