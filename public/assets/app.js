document.querySelectorAll("[data-confirm]").forEach((form) =>
  form.addEventListener("submit", (event) => {
    if (!confirm(form.dataset.confirm)) event.preventDefault();
  }),
);
document.querySelectorAll("[data-save-form]").forEach((form) =>
  form.addEventListener("submit", (event) => {
    if (form.dataset.submitting) {
      event.preventDefault();
      return;
    }
    form.dataset.submitting = "1";
    form.setAttribute("aria-busy", "true");
    setTimeout(
      () =>
        form
          .querySelectorAll('button[type="submit"], .form-footer button')
          .forEach((b) => (b.disabled = true)),
      0,
    );
  }),
);
const upload = document.querySelector("#cover");
let previewUrl;
if (upload)
  upload.addEventListener("change", () => {
    const file = upload.files[0];
    document.querySelector("#cover-filename").textContent = file ? file.name : "Ninguna imagen seleccionada";
    upload.setCustomValidity("");
    if (!file) return;
    if (
      file.size > 5 * 1024 * 1024 ||
      !["image/jpeg", "image/png", "image/webp"].includes(file.type)
    ) {
      upload.setCustomValidity(
        "Elegí una imagen JPEG, PNG o WebP de hasta 5 MB.",
      );
      upload.reportValidity();
      return;
    }
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    previewUrl = URL.createObjectURL(file);
    const img = document.createElement("img");
    img.src = previewUrl;
    img.alt = "Vista previa de la tapa seleccionada";
    document.querySelector("#cover-preview").replaceChildren(img);
  });
