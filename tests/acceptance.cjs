// Pruebas HTTP y de interfaz sobre una instalación temporal, nunca sobre el catálogo real.
const { chromium } = require("playwright");
const { spawn, spawnSync } = require("node:child_process");
const fs = require("node:fs");
const os = require("node:os");
const path = require("node:path");
const crypto = require("node:crypto");
const assert = require("node:assert/strict");
const root = path.resolve(__dirname, "..");
const temp = fs.mkdtempSync(path.join(os.tmpdir(), "ged-test-"));
const php = process.env.GED_PHP || "php";
fs.mkdirSync(temp + "/covers");
const config = temp + "/config.php";
fs.writeFileSync(
  config,
  `<?php return ['dsn'=>'sqlite:${temp}/ged.sqlite','user'=>null,'password'=>null,'covers'=>'${temp}/covers','publisher'=>'Editorial de prueba'];`,
);
const env = { ...process.env, GED_CONFIG: config };
let password = crypto.randomBytes(18).toString("hex");
const email = "pruebas@example.test";
function cli(args, input) {
  const p = spawnSync(php, [root + "/bin/console.php", ...args], {
    env,
    input,
    encoding: "utf8",
  });
  assert.equal(p.status, 0, p.stderr);
  return p.stdout;
}
function sql(query) {
  const p = spawnSync(
    php,
    [
      "-r",
      `$c=require getenv('GED_CONFIG');$d=new PDO($c['dsn']);echo json_encode($d->query(${JSON.stringify(query)})->fetchAll(PDO::FETCH_ASSOC));`,
    ],
    { env, encoding: "utf8" },
  );
  assert.equal(p.status, 0, p.stderr);
  return JSON.parse(p.stdout);
}
let server;
let browser;
let checks = 0;
const pass = (x) => {
  checks++;
  console.log("OK " + x);
};
const prefix = process.env.GED_TEST_PREFIX || "";
const url = "http://127.0.0.1:8766" + prefix;
let documentRoot = root + "/public";
if (prefix) {
  assert.equal(prefix, "/ged");
  const privateRoot = temp + "/ged-private";
  fs.mkdirSync(privateRoot);
  for (const entry of ["app", "views", "public", "config", "migrations"]) {
    fs.cpSync(root + "/" + entry, privateRoot + "/" + entry, {recursive: true});
  }
  documentRoot = temp + "/public_html";
  fs.mkdirSync(documentRoot + "/ged", {recursive: true});
  fs.cpSync(root + "/public/assets", documentRoot + "/ged/assets", {recursive: true});
  fs.copyFileSync(root + "/deploy/cpanel-index.php", documentRoot + "/ged/index.php");
}
function start() {
  server = spawn(
    php,
    [
      "-d",
      "upload_max_filesize=6M",
      "-d",
      "post_max_size=8M",
      "-S",
      "127.0.0.1:8766",
      "-t",
      documentRoot,
    ],
    { env, stdio: ["ignore", "ignore", "pipe"] },
  );
  server.stderr.on("data", (d) => {
    if (/Fatal|Warning|Uncaught/.test(d)) process.stderr.write(d);
  });
}
async function ready() {
  for (let i = 0; i < 40; i++) {
    try {
      await fetch(url);
      return;
    } catch {
      await new Promise((r) => setTimeout(r, 100));
    }
  }
  throw Error("Servidor no disponible");
}
async function submit(page, label) {
  await Promise.all([
    page.waitForNavigation(),
    page.getByRole("button", { name: label, exact: true }).click(),
  ]);
}
async function login(page, p = password) {
  await page.goto(url + "/?action=login");
  await page.getByLabel("Correo electrónico").fill(email);
  await page.getByLabel("Contraseña", { exact: true }).fill(p);
  await submit(page, "Ingresar");
}
async function screenshot(page, name) {
  await page.screenshot({ path: temp + "/" + name + ".png", fullPage: true });
}
(async () => {
  try {
    cli(["check"]);
    cli(["migrate"]);
    cli(["migrate"]);
    assert.equal(sql("SELECT * FROM books").length, 0);
    cli(["create-master", email], password + "\n" + password + "\n");
    pass(
      "Instalación limpia, migraciones repetibles y usuario maestro por consola",
    );
    start();
    await ready();
    browser = await chromium.launch({
      headless: true,
      executablePath: process.env.CHROMIUM_PATH || "/usr/bin/chromium",
      args: ["--no-sandbox"],
    });
    const context = await browser.newContext();
    const page = await context.newPage();
    page.setDefaultTimeout(10000);
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.goto(url + "/?action=new");
    assert.match(page.url(), /action=login/);
    const noauth = await context.request.post(url + "/?action=toggle&id=1", {
      form: {},
    });
    assert.match(noauth.url(), /action=login/);
    pass("Catálogo y operaciones protegidos sin autenticación");
    await screenshot(page, "login-desktop");
    await login(page, "incorrecta");
    await page.getByText("Correo o contraseña incorrectos.").waitFor();
    await login(page);
    await page.getByText("Tu biblioteca empieza acá").waitFor();
    await screenshot(page, "empty-desktop");
    pass("Acceso incorrecto y correcto; biblioteca vacía");
  assert.equal(new URL(await page.locator("a.brand").getAttribute("href"), url).pathname, prefix + "/");
  const cssPath = await page.locator('link[rel="stylesheet"]').getAttribute("href");
  assert.equal(cssPath, prefix + "/assets/app.css");
  assert.equal((await context.request.get("http://127.0.0.1:8766" + cssPath)).status(), 200);
  const cookie = (await context.cookies()).find(c => c.name === "ged_session");
  assert.equal(cookie.path, prefix + "/");
  pass("Rutas de navegación, CSS y cookie dentro del directorio de instalación");
    let response = await context.request.post(url + "/?action=new", {
      form: { code: "INVALID", title: "Sin CSRF" },
    });
    assert.equal(response.status(), 403);
    pass("Rechazo de CSRF ausente");
    await page.getByRole("link", { name: "Cambiar contraseña" }).click();
    await page.getByLabel("Contraseña actual").fill(password);
    const newPassword = crypto.randomBytes(18).toString("hex");
    await page
      .getByLabel("Nueva contraseña", { exact: true })
      .fill(newPassword);
    await page.getByLabel("Repetir nueva contraseña").fill(newPassword);
    await submit(page, "Guardar contraseña");
    password = newPassword;
    await page.getByText("Contraseña actualizada.").waitFor();
    await submit(page, "Cerrar sesión");
    await login(page);
    pass("Cambio de contraseña y cierre de sesión");
    await page.getByRole("link", { name: "Nuevo título", exact: true }).click();
    await page.getByLabel("Código corto").fill(" eac ");
    await page
      .getByLabel("Título *", { exact: true })
      .fill("El arte de comenzar");
    await page.getByLabel("Autor", { exact: true }).fill("María del Sur");
    await page.getByLabel("ISBN", { exact: true }).fill("9780306406157");
    await page.getByLabel("PVP · pesos argentinos").fill("24500");
    await page.locator("summary").click();
    await page.getByLabel("Año de publicación").fill("2026");
    await page.getByLabel("Ancho · cm").fill("14,5");
    await page.getByLabel("Alto · cm").fill("21");
    await page.getByLabel("Cantidad de páginas").fill("248");
    await page
      .getByLabel("Sinopsis", { exact: true })
      .fill("Una historia sobre los pequeños comienzos.");
    // Imagen PNG real creada por Chromium: valida contenido y dimensiones.
    const imagePage = await context.newPage();
    await imagePage.setContent(
      '<html><body style="margin:0;background:#274d43;width:300px;height:420px;color:#efdfb4"><h1 style="padding:40px;font:42px Georgia">El arte de comenzar</h1><p style="padding:40px">María del Sur</p></body></html>',
    );
    await imagePage.setViewportSize({ width: 300, height: 420 });
    await imagePage.screenshot({ path: temp + "/cover.png" });
    await imagePage.close();
    await page.locator("#cover").setInputFiles(temp + "/cover.png");
    const submission = await page.locator("[name=submission]").inputValue();
    const csrfToken = await page.locator("[data-save-form] [name=csrf]").inputValue();
    await screenshot(page, "form-desktop");
    await submit(page, "Guardar y abrir ficha →");
    await page
      .getByRole("heading", { name: "El arte de comenzar", exact: true })
      .waitFor();
    const id = Number(new URL(page.url()).searchParams.get("id"));
    assert.equal(sql("SELECT * FROM books")[0].code, "EAC");
    assert.equal(sql("SELECT * FROM books")[0].pvp, 24500);
    await page.getByText("$ 24.500", { exact: true }).waitFor();
    assert.equal(fs.readdirSync(temp + "/covers").length, 1);
    await screenshot(page, "detail-desktop");
    pass("Alta, normalización, ISBN, PVP entero, ficha y carga de tapa");
    const replay = await context.request.post(url + "/?action=new", {
      form: { csrf: csrfToken, submission, code: "EAC", title: "Reenvío" },
    });
    assert.equal(replay.status(), 409);
    assert.equal(sql("SELECT * FROM books").length, 1);
    pass("Reenvío de formulario no duplica el registro");
    await page.getByRole("link", { name: "Editar título" }).click();
    await page
      .getByLabel("Título *", { exact: true })
      .fill("El arte de comenzar de nuevo");
    await page.locator("#cover").setInputFiles(temp + "/cover.png");
    await submit(page, "Guardar y abrir ficha →");
    assert.equal(Number(new URL(page.url()).searchParams.get("id")), id);
    assert.equal(fs.readdirSync(temp + "/covers").length, 1);
    pass("Modificación conserva ID y reemplazo de tapa sin huérfanos");
    await page.getByRole("link", { name: "Editar título" }).click();
    await page.getByLabel("Quitar tapa actual").check();
    await submit(page, "Guardar y abrir ficha →");
    assert.equal(fs.readdirSync(temp + "/covers").length, 0);
    pass("Quitar tapa elimina el archivo");
    await page.goto(url + "/?action=new");
    await page.getByLabel("Código corto").fill("eac");
    await page.getByLabel("Título *", { exact: true }).fill("Duplicado");
    await page.getByLabel("ISBN", { exact: true }).fill("9780306406157");
    await submit(page, "Guardar y abrir ficha →");
    await page.getByText("Ya existe un título con este código.").waitFor();
    await page.getByText("Ya existe un título con este ISBN.").waitFor();
    assert.equal(
      await page.getByLabel("Título *", { exact: true }).inputValue(),
      "Duplicado",
    );
    pass("Códigos e ISBN duplicados rechazados y formulario conservado");
    await page
      .locator("form[data-save-form]")
      .evaluate((f) => (f.noValidate = true));
    await page.getByLabel("Código corto").fill("EVF");
    await page.getByLabel("ISBN", { exact: true }).fill("123");
    await page.getByLabel("PVP · pesos argentinos").fill("0");
    await submit(page, "Guardar y abrir ficha →");
    await page
      .getByText(
        "Ingresá un ISBN-10 o ISBN-13 válido, con su dígito de control.",
      )
      .waitFor();
    await page.getByText("Ingresá un entero entre 1 y 999999999999.").waitFor();
    pass("ISBN inválido y PVP cero rechazados");
    await page.getByLabel("ISBN", { exact: true }).fill("");
    await page.getByLabel("PVP · pesos argentinos").fill("");
    await submit(page, "Guardar y cargar otro");
    await page.getByRole("heading", { name: "Nuevo título." }).waitFor();
    assert.equal(sql("SELECT pvp FROM books WHERE code='EVF'")[0].pvp, null);
    pass("Ficha incompleta y PVP pendiente conservado como NULL");
    await page.goto(url + "/?q=María&status=active");
    await page.getByText("1 título", { exact: true }).waitFor();
    await page.goto(url + "/?q=9780306406157&status=active");
    await page.getByText("1 título", { exact: true }).waitFor();
    await page.goto(url + "/?q=EAC&status=active");
    await page.getByText("1 título", { exact: true }).waitFor();
    pass("Búsquedas por autor, ISBN y código");
    await page.goto(url + "/?action=detail&id=" + id);
    page.once("dialog", (d) => d.accept());
    await submit(page, "Desactivar");
    await page.getByText("Título desactivado.").waitFor();
    await page.goto(url + "/?status=inactive");
    await page.getByText("1 título", { exact: true }).waitFor();
    await page.goto(url + "/?action=detail&id=" + id);
    await submit(page, "Reactivar");
    assert.equal(sql("SELECT active FROM books WHERE id=" + id)[0].active, 1);
    pass("Desactivación confirmada, filtro de inactivos y reactivación");
    await page.goto(url + "/?action=edit&id=" + id);
    await page
      .locator("#cover")
      .setInputFiles({
        name: "falsa.png",
        mimeType: "image/png",
        buffer: Buffer.from("not an image"),
      });
    await submit(page, "Guardar y abrir ficha →");
    await page.getByText(/Usá una imagen JPEG, PNG o WebP válida/).waitFor();
    pass("Archivo de tapa falsificado rechazado por el servidor");
    await page.locator("#cover").setInputFiles(temp + "/cover.png");
    await submit(page, "Guardar y abrir ficha →");
    await page.goto(url);
    await screenshot(page, "library-desktop");
    await page.setViewportSize({ width: 390, height: 844 });
    await screenshot(page, "library-mobile");
    assert.equal(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
      true,
    );
    await page.goto(url + "/?action=detail&id=" + id);
    await screenshot(page, "detail-mobile");
    await page.getByRole("link", { name: "Editar título" }).click();
    await screenshot(page, "form-mobile");
    assert.equal(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
      true,
    );
    pass("Pantallas capturadas en computadora y móvil, sin desborde de página");
    await page.goto(url + "/?sort=code");
    assert.deepEqual(
      await page.locator("tbody tr td:first-child").evaluateAll(nodes => nodes.map(node => node.textContent.trim())),
      ["EAC", "EVF"],
    );
    await page.goto(url + "/?sort=title");
    assert.deepEqual(
      await page.locator("tbody tr td:first-child").evaluateAll(nodes => nodes.map(node => node.textContent.trim())),
      ["EVF", "EAC"],
    );
    await page.goto(url + "/?sort=pvp");
    assert.deepEqual(
      await page.locator("tbody tr td:first-child").evaluateAll(nodes => nodes.map(node => node.textContent.trim())),
      ["EAC", "EVF"],
    );
    pass("Orden por código, título y PVP; pendientes al final");
    sql(
      "WITH RECURSIVE seq(x) AS (SELECT 1 UNION ALL SELECT x+1 FROM seq WHERE x<21) INSERT INTO books(code,title,pvp,created_at,updated_at) SELECT 'T'||x,'Título '||x,100+x,datetime('now'),datetime('now') FROM seq",
    );
    await page.goto(url);
    assert.equal(await page.locator("tbody tr").count(), 20);
    await page.getByRole("link", { name: "Siguiente →" }).click();
    assert.equal(await page.locator("tbody tr").count(), 3);
    await page.getByText("Página 2 de 2").waitFor();
    pass("Paginación real con 23 títulos");

    await new Promise((resolve) => {
      server.once("exit", resolve);
      server.kill();
    });
    start();
    await ready();
    await page.goto(url + "/?action=detail&id=" + id);
    await page
      .getByRole("heading", {
        name: "El arte de comenzar de nuevo",
        exact: true,
      })
      .waitFor();
    pass("Persistencia tras reiniciar servidor");
    await new Promise((resolve) => {
      server.once("exit", resolve);
      server.kill();
    });
    cli(["backup", temp + "/backup"]);
    assert.equal(fs.readdirSync(temp + "/backup/covers").length, 1);
    fs.renameSync(temp + "/ged.sqlite", temp + "/old.sqlite");
    fs.copyFileSync(temp + "/backup/ged.sqlite", temp + "/ged.sqlite");
    fs.rmSync(temp + "/covers", { recursive: true });
    fs.cpSync(temp + "/backup/covers", temp + "/covers", { recursive: true });
    start();
    await ready();
    await page.goto(url + "/?action=detail&id=" + id);
    await page
      .getByRole("heading", {
        name: "El arte de comenzar de nuevo",
        exact: true,
      })
      .waitFor();
    const cover = await context.request.get(url + "/?action=cover&id=" + id);
    assert.equal(cover.status(), 200);
    assert.equal(cover.headers()["content-type"], "image/png");
    pass("Respaldo y restauración de base y tapas");
    await submit(page, "Cerrar sesión");
    for (let i = 0; i < 6; i++) await login(page, "incorrecta");
    await page
      .getByText(
        "Demasiados intentos. Esperá 15 minutos antes de volver a intentar.",
      )
      .waitFor();
    pass("Limitación de intentos de acceso");
    cli(["reset-password", email], password + "\n" + password + "\n");
    await login(page);
    await page
      .getByRole("heading", { name: "Biblioteca de títulos." })
      .waitFor();
    pass("Recuperación de contraseña por consola");
    console.log(`${checks} comprobaciones completadas. Capturas: ${temp}`);
  } finally {
    if (browser) await browser.close();
    if (server && !server.killed) server.kill();
  }
})().catch((e) => {
  console.error(e);
  process.exitCode = 1;
});
