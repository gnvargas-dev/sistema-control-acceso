/* Sistema Escolar · tema.js (Etapa 6: login con cerradura animada y fetch)
   Ruta: public/js/tema.js
   Si algo falla, el formulario se envía de forma tradicional (HTML normal). */
(function () {
    'use strict';

    function iniciarLogin() {
        var form = document.querySelector('[data-se-lg-form]');
        if (!form) return;

        var tarjeta = document.querySelector('[data-se-lg-tarjeta]');
        var cerradura = document.querySelector('[data-se-lg-cerradura]');
        var error = document.querySelector('[data-se-lg-error]');
        var boton = form.querySelector('[data-se-lg-enviar]');
        var textoBoton = boton.querySelector('.se-lg-enviar-texto');
        var usuario = form.querySelector('#cedula');
        var clave = form.querySelector('#password');
        var ver = form.querySelector('[data-se-lg-ver]');
        var ocupado = false;

        function marcar(el, clase, activo) {
            if (!el) return;
            if (el instanceof SVGElement) {
                var actual = (el.getAttribute('class') || '').split(/\s+/).filter(Boolean);
                var i = actual.indexOf(clase);
                if (activo && i === -1) actual.push(clase);
                if (!activo && i !== -1) actual.splice(i, 1);
                el.setAttribute('class', actual.join(' '));
            } else {
                el.classList.toggle(clase, activo);
            }
        }

        /* Anillo: cada campo completo aporta la mitad */
        function progreso() {
            var p = (usuario.value.trim().length >= 5 ? 0.5 : 0) + (clave.value.length >= 4 ? 0.5 : 0);
            cerradura.style.setProperty('--p', p);
        }

        function limpiarError() {
            error.textContent = '';
            error.classList.remove('visible');
            marcar(cerradura, 'mal', false);
        }

        function mostrarError(mensaje) {
            error.textContent = mensaje || 'No se pudo iniciar sesión. Revisá tus datos.';
            error.classList.add('visible');

            tarjeta.classList.remove('se-lg-tiembla');
            void tarjeta.offsetWidth;
            tarjeta.classList.add('se-lg-tiembla');

            marcar(cerradura, 'mal', false);
            void cerradura.getBoundingClientRect();
            marcar(cerradura, 'mal', true);
            setTimeout(function () { marcar(cerradura, 'mal', false); }, 900);

            boton.classList.remove('cargando');
            textoBoton.textContent = 'Ingresar';
            ocupado = false;
            clave.focus();
            clave.select();
        }

        function exito(destino) {
            boton.classList.remove('cargando');
            boton.classList.add('ok');
            textoBoton.textContent = '¡Bienvenido!';
            marcar(cerradura, 'abierta', true);
            setTimeout(function () { window.location.href = destino; }, 900);
        }

        /* Fallback: envío HTML tradicional (form.submit no dispara el evento submit) */
        function envioNormal() {
            try { form.submit(); } catch (e) { HTMLFormElement.prototype.submit.call(form); }
        }

        function primerError(json) {
            if (!json) return '';
            if (json.errors) {
                var k = Object.keys(json.errors)[0];
                var v = json.errors[k];
                return Array.isArray(v) ? v[0] : String(v);
            }
            return json.message || '';
        }

        form.addEventListener('input', function () { progreso(); });

        if (ver) {
            ver.addEventListener('click', function () {
                var oculta = clave.type === 'password';
                clave.type = oculta ? 'text' : 'password';
                ver.textContent = oculta ? 'Ocultar' : 'Mostrar';
                ver.setAttribute('aria-pressed', oculta ? 'true' : 'false');
                ver.setAttribute('aria-label', oculta ? 'Ocultar contraseña' : 'Mostrar contraseña');
            });
        }

        form.addEventListener('submit', function (e) {
            if (ocupado) { e.preventDefault(); return; }

            /* Validación mínima en cliente: campos vacíos */
            if (!usuario.value.trim() || !clave.value) {
                e.preventDefault();
                mostrarError('Completá tu cédula y contraseña.');
                (usuario.value.trim() ? clave : usuario).focus();
                return;
            }

            if (!window.fetch || !window.FormData) return; /* envío normal */

            e.preventDefault();
            ocupado = true;
            limpiarError();
            boton.classList.add('cargando');

            var tokenMeta = document.querySelector('meta[name="csrf-token"]');
            var cabeceras = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
            if (tokenMeta) cabeceras['X-CSRF-TOKEN'] = tokenMeta.content;

            fetch(form.action, {
                method: 'POST',
                headers: cabeceras,
                body: new FormData(form),
                credentials: 'same-origin',
                redirect: 'follow'
            }).then(function (res) {
                var tipo = res.headers.get('content-type') || '';

                if (res.status === 419) {
                    mostrarError('La página caducó. Recargá e intentá de nuevo.');
                    setTimeout(function () { window.location.reload(); }, 1600);
                    return;
                }
                if (res.status === 429) {
                    mostrarError('Demasiados intentos. Esperá un momento e intentá de nuevo.');
                    return;
                }

                /* Respuesta JSON (422 u otra) */
                if (tipo.indexOf('application/json') !== -1) {
                    return res.json().then(function (json) {
                        if (res.ok && !json.errors) {
                            exito(json.redirect || json.redirect_url || json.intended || res.url || '/');
                        } else {
                            mostrarError(primerError(json));
                        }
                    });
                }

                /* Respuesta HTML tras seguir redirecciones */
                return res.text().then(function (html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var sigueLogin = doc.querySelector('[data-se-lg-form]');

                    if (sigueLogin) {
                        var msg = doc.querySelector('[data-se-lg-error]');
                        mostrarError(msg ? msg.textContent.trim() : '');
                    } else if (res.ok) {
                        exito(res.url || '/');
                    } else {
                        throw new Error('Respuesta inesperada ' + res.status);
                    }
                });
            }).catch(function () {
                /* Error de red o de script: envío tradicional */
                ocupado = false;
                envioNormal();
            });
        });

        /* Estado inicial: si el servidor devolvió error, anillo en rojo y temblor ya aplicados */
        progreso();
        window.addEventListener('pageshow', function (ev) {
            if (ev.persisted) {
                ocupado = false;
                boton.classList.remove('cargando', 'ok');
                textoBoton.textContent = 'Ingresar';
                marcar(cerradura, 'abierta', false);
                progreso();
            }
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciarLogin);
    else iniciarLogin();
})();

/* ==========================================================================
   Etapa 7: paneles (indicador de menú, contadores, pestañas de semestre,
   cálculo de nota final, escala de 1 a 5). Bloque aparte: no toca el login.
   ========================================================================== */
(function () {
    'use strict';

    var reducir = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    /* 1. Indicador deslizante de la sección activa */
    function iniciarIndicador() {
        var nav = document.querySelector('[data-se-menu]');
        if (!nav || nav.querySelector('.se-menu-indicador')) return;

        var activo = nav.querySelector('a.activo');
        if (!activo) return;

        var ind = document.createElement('span');
        ind.className = 'se-menu-indicador quieto';
        ind.setAttribute('aria-hidden', 'true');
        nav.insertBefore(ind, nav.firstChild);

        function medir() { return { y: activo.offsetTop, h: activo.offsetHeight }; }

        function poner(m) {
            ind.style.setProperty('--y', m.y + 'px');
            ind.style.setProperty('--h', m.h + 'px');
        }

        function guardar(m) {
            try { sessionStorage.setItem('se-menu-pos', JSON.stringify(m)); } catch (e) {}
        }

        var destino = medir();
        var previo = null;
        try { previo = JSON.parse(sessionStorage.getItem('se-menu-pos') || 'null'); } catch (e) {}

        var animar = !reducir && previo && typeof previo.y === 'number' && typeof previo.h === 'number' &&
            (previo.y !== destino.y || previo.h !== destino.h);

        nav.classList.add('se-menu-vivo');
        poner(animar ? previo : destino);
        void ind.offsetWidth;
        ind.classList.remove('quieto');

        if (animar) requestAnimationFrame(function () { poner(destino); });
        guardar(destino);

        function remedir() {
            var m = medir();
            ind.classList.add('quieto');
            poner(m);
            void ind.offsetWidth;
            ind.classList.remove('quieto');
            guardar(m);
        }

        window.addEventListener('resize', remedir);
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(remedir);
    }

    /* 2. Contadores animados: [data-se-contar] con texto "248", "94%" o "8,45" */
    function iniciarContadores() {
        if (reducir) return;

        document.querySelectorAll('[data-se-contar]').forEach(function (el) {
            var texto = el.textContent.trim();
            var m = texto.match(/^(\d+)(?:([.,])(\d{1,2}))?(%?)$/);
            if (!m) return;

            var dec = m[3] ? m[3].length : 0;
            var coma = m[2] === ',';
            var fin = parseFloat(m[1] + '.' + (m[3] || '0'));
            var t0 = null;

            function formato(v) {
                var s = v.toFixed(dec);
                return (coma ? s.replace('.', ',') : s) + m[4];
            }

            function paso(t) {
                if (t0 === null) t0 = t;
                var k = Math.min((t - t0) / 900, 1);
                if (k < 1) {
                    el.textContent = formato(fin * (1 - Math.pow(1 - k, 3)));
                    requestAnimationFrame(paso);
                } else {
                    el.textContent = texto;
                }
            }

            el.textContent = formato(0);
            requestAnimationFrame(paso);
        });
    }

    /* 3. Pestañas de semestre: [data-se-tabs] > [data-se-tab="id|todos"], paneles [data-se-panel="id"] */
    function iniciarPestanas() {
        document.querySelectorAll('[data-se-tabs]').forEach(function (barra) {
            var botones = Array.prototype.slice.call(barra.querySelectorAll('[data-se-tab]'));
            var contexto = barra.parentNode;
            var paneles = Array.prototype.slice.call(contexto.querySelectorAll('[data-se-panel]'));
            if (!botones.length || !paneles.length) return;

            function mostrar(id, enfocar) {
                botones.forEach(function (b) {
                    var on = b.getAttribute('data-se-tab') === id;
                    b.setAttribute('aria-selected', on ? 'true' : 'false');
                    b.tabIndex = on ? 0 : -1;
                    if (on && enfocar) b.focus();
                });

                paneles.forEach(function (p) {
                    var ver = id === 'todos' || p.getAttribute('data-se-panel') === id;
                    var estabaOculto = p.hidden;
                    p.hidden = !ver;
                    p.classList.remove('se-entra');
                    if (ver && estabaOculto) {
                        void p.offsetWidth;
                        p.classList.add('se-entra');
                    }
                });
            }

            barra.hidden = false;
            mostrar(botones[0].getAttribute('data-se-tab'), false);

            botones.forEach(function (b, i) {
                b.addEventListener('click', function () { mostrar(b.getAttribute('data-se-tab'), false); });
                b.addEventListener('keydown', function (e) {
                    var paso = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
                    if (!paso) return;
                    e.preventDefault();
                    mostrar(botones[(i + paso + botones.length) % botones.length].getAttribute('data-se-tab'), true);
                });
            });
        });
    }

    function iniciarPaneles() {
        iniciarIndicador();
        iniciarContadores();
        iniciarPestanas();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciarPaneles);
    else iniciarPaneles();
})();

/* ==========================================================================
   Etapa 8A: spinner de guardado y modal de confirmación accesible.
   Bloque aparte: no toca el login ni los paneles. Sin dependencias.
   ========================================================================== */
(function () {
    'use strict';

    var TIEMPO_SPINNER = 4000;
    var reproduciendo = false;     /* true mientras se reenvía una acción ya confirmada */

    /* ---------------------------------------------------------------
       1. Spinner de guardado
       No deshabilita botones (name/value del botón pulsado siguen viajando).
       Se retira solo a los 4 s: si la página no navegó (descarga de PDF,
       target externo, etc.) no queda trabado.
       --------------------------------------------------------------- */
    var spinner = null;
    var spinnerTexto = null;
    var spinnerTimer = null;

    function crearSpinner() {
        if (spinner) return;
        spinner = document.createElement('div');
        spinner.className = 'se-spinner';
        spinner.setAttribute('role', 'status');
        spinner.setAttribute('aria-live', 'polite');
        spinner.hidden = true;

        var rueda = document.createElement('span');
        rueda.className = 'se-spinner-rueda';
        rueda.setAttribute('aria-hidden', 'true');

        spinnerTexto = document.createElement('span');
        spinnerTexto.className = 'se-spinner-texto';

        spinner.appendChild(rueda);
        spinner.appendChild(spinnerTexto);
        document.body.appendChild(spinner);
    }

    function ocultarSpinner() {
        clearTimeout(spinnerTimer);
        spinnerTimer = null;
        if (spinner) spinner.hidden = true;
    }

    function mostrarSpinner(texto) {
        crearSpinner();
        clearTimeout(spinnerTimer);
        spinnerTexto.textContent = texto;
        spinner.hidden = false;
        spinnerTimer = setTimeout(ocultarSpinner, TIEMPO_SPINNER);
    }

    /* Etapa 8D: para trabajos con fetch (sin envío de formulario), mismo spinner y mismo tope de 4 s */
    window.seSpinner = { mostrar: mostrarSpinner, ocultar: ocultarSpinner };

    /* Fase de burbuja: corre después de los manejadores propios del formulario,
       así sabemos si alguien canceló el envío (confirm() nativo, validaciones, fetch). */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (e.defaultPrevented) return;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.hasAttribute('data-se-lg-form')) return;          /* el login tiene su propio estado */
        if (form.hasAttribute('data-se-sin-spinner')) return;
        if (/^_(blank|new)$/i.test(form.getAttribute('target') || '')) return;

        var metodo = (form.getAttribute('method') || 'get').toLowerCase();
        mostrarSpinner(metodo === 'get' ? 'Cargando…' : 'Guardando…');
    });

    /* Volver con el botón "atrás" (bfcache) no debe mostrar un spinner viejo */
    window.addEventListener('pageshow', function (e) { if (e.persisted) ocultarSpinner(); });
    window.addEventListener('pagehide', ocultarSpinner);

    /* ---------------------------------------------------------------
       2. Modal de confirmación (<dialog> nativo)
       - showModal(): fondo inerte, ESC cierra, foco vuelve al origen.
       - Tab/Shift+Tab ciclan entre los botones del modal (foco atrapado).
       - Si el navegador no soporta <dialog>, no se intercepta nada y
         sigue funcionando el confirm() original.
       --------------------------------------------------------------- */
    var soportaDialog = typeof HTMLDialogElement === 'function' &&
        typeof HTMLDialogElement.prototype.showModal === 'function';

    var dialogo = null, dTitulo = null, dMensaje = null, dCancelar = null, dAceptar = null;
    var origenFoco = null;
    var alAceptar = null;

    function crearDialogo() {
        if (dialogo) return;

        dialogo = document.createElement('dialog');
        dialogo.className = 'se-modal';
        dialogo.setAttribute('aria-labelledby', 'se-modal-titulo');
        dialogo.setAttribute('aria-describedby', 'se-modal-mensaje');

        var caja = document.createElement('div');
        caja.className = 'se-modal-caja';

        var icono = document.createElement('span');
        icono.className = 'se-modal-icono';
        icono.setAttribute('aria-hidden', 'true');
        icono.textContent = '!';

        dTitulo = document.createElement('h2');
        dTitulo.id = 'se-modal-titulo';
        dTitulo.className = 'se-modal-titulo';

        dMensaje = document.createElement('p');
        dMensaje.id = 'se-modal-mensaje';
        dMensaje.className = 'se-modal-mensaje';

        var acciones = document.createElement('div');
        acciones.className = 'se-modal-acciones';

        dCancelar = document.createElement('button');
        dCancelar.type = 'button';
        dCancelar.className = 'se-modal-btn se-modal-btn--cancelar';
        dCancelar.textContent = 'Cancelar';

        dAceptar = document.createElement('button');
        dAceptar.type = 'button';
        dAceptar.className = 'se-modal-btn se-modal-btn--aceptar';

        acciones.appendChild(dCancelar);
        acciones.appendChild(dAceptar);
        caja.appendChild(icono);
        caja.appendChild(dTitulo);
        caja.appendChild(dMensaje);
        caja.appendChild(acciones);
        dialogo.appendChild(caja);
        document.body.appendChild(dialogo);

        dCancelar.addEventListener('click', function () { dialogo.close('cancelar'); });

        dAceptar.addEventListener('click', function () { dialogo.close('aceptar'); });

        /* Clic en el fondo oscuro (el propio <dialog>, fuera de la caja) cancela */
        dialogo.addEventListener('click', function (e) {
            if (e.target === dialogo) dialogo.close('cancelar');
        });

        /* ESC: el navegador dispara "cancel" y cierra solo; nos aseguramos del returnValue */
        dialogo.addEventListener('cancel', function () { dialogo.returnValue = 'cancelar'; });

        /* Foco atrapado: solo hay dos botones, se alternan */
        dialogo.addEventListener('keydown', function (e) {
            if (e.key !== 'Tab') return;
            var primero = dCancelar, ultimo = dAceptar;
            if (e.shiftKey && document.activeElement === primero) {
                e.preventDefault();
                ultimo.focus();
            } else if (!e.shiftKey && document.activeElement === ultimo) {
                e.preventDefault();
                primero.focus();
            } else if (document.activeElement !== primero && document.activeElement !== ultimo) {
                e.preventDefault();
                primero.focus();
            }
        });

        dialogo.addEventListener('close', function () {
            var aceptado = dialogo.returnValue === 'aceptar';
            var accion = alAceptar;
            var destino = origenFoco;
            alAceptar = null;
            origenFoco = null;
            dialogo.returnValue = '';

            if (aceptado && accion) {
                accion();
            } else if (destino && document.contains(destino) && typeof destino.focus === 'function') {
                destino.focus();
            }
        });
    }

    function confirmarModal(opciones, callback) {
        crearDialogo();
        origenFoco = document.activeElement;
        alAceptar = callback;

        dTitulo.textContent = opciones.titulo;
        dMensaje.textContent = opciones.mensaje;
        dAceptar.textContent = opciones.aceptar;
        dAceptar.classList.toggle('se-modal-btn--peligro', !!opciones.peligro);
        dAceptar.classList.toggle('se-modal-btn--normal', !opciones.peligro);

        ocultarSpinner();
        dialogo.returnValue = '';
        dialogo.showModal();
        dCancelar.focus();     /* foco inicial en la opción segura */
    }

    /* Ejecuta una acción ya confirmada haciendo que el confirm() original
       (inline o de un <script> de la vista) responda "true" solo durante ese instante. */
    function reproducir(fn) {
        var original = window.confirm;
        reproduciendo = true;
        window.confirm = function () { return true; };
        try { fn(); }
        finally {
            window.confirm = original;
            reproduciendo = false;
        }
    }

    /* Texto del botón de confirmar: lo que dice el botón de origen */
    function opcionesPara(origen, mensaje) {
        var etiqueta = '';
        if (origen) {
            etiqueta = origen.getAttribute('data-se-confirmar-texto') ||
                origen.getAttribute('aria-label') ||
                (origen.textContent || '').replace(/\s+/g, ' ').trim();
        }
        var base = (etiqueta + ' ' + mensaje).toLowerCase();
        var peligro = /elimin|quit|borr/.test(base);

        return {
            titulo: (origen && origen.getAttribute('data-se-confirmar-titulo')) || 'Confirmá la acción',
            mensaje: mensaje,
            aceptar: etiqueta && etiqueta.length <= 40 ? etiqueta : 'Confirmar',
            peligro: peligro
        };
    }

    /* Mensaje de un onsubmit="return confirm('...')" */
    function mensajeInline(form) {
        var codigo = form.getAttribute('onsubmit') || '';
        var m = codigo.match(/confirm\(\s*(['"])((?:\\.|(?!\1)[^\\])*)\1\s*\)/);
        return m ? m[2].replace(/\\(.)/g, '$1') : '';
    }

    function reenviarFormulario(form, boton) {
        if (typeof form.requestSubmit === 'function') {
            if (boton && boton.form === form) form.requestSubmit(boton);
            else form.requestSubmit();
            return;
        }
        /* Navegadores sin requestSubmit: conservar name/value del botón pulsado */
        if (boton && boton.name) {
            var oculto = document.createElement('input');
            oculto.type = 'hidden';
            oculto.name = boton.name;
            oculto.value = boton.value;
            form.appendChild(oculto);
        }
        HTMLFormElement.prototype.submit.call(form);
    }

    if (soportaDialog) {
        /* 2a. Botones con data-se-confirmar (cargar-notas: "Quitar" parcial/final).
               Fase de captura: corre antes que el listener de la vista. */
        document.addEventListener('click', function (e) {
            if (reproduciendo || e.defaultPrevented) return;
            var el = e.target instanceof Element ? e.target : null;
            var boton = el ? el.closest('[data-se-confirmar]') : null;
            if (!boton || boton.tagName === 'FORM') return;

            var mensaje = boton.getAttribute('data-se-confirmar');
            if (!mensaje) return;

            e.preventDefault();
            e.stopImmediatePropagation();

            confirmarModal(opcionesPara(boton, mensaje), function () {
                reproducir(function () { boton.click(); });
            });
        }, true);

        /* 2b. Formularios con onsubmit="return confirm('...')" (admin/alumnos)
               o con data-se-confirmar en el <form>. Captura: antes del onsubmit inline. */
        document.addEventListener('submit', function (e) {
            if (reproduciendo) return;
            var form = e.target;
            if (!(form instanceof HTMLFormElement)) return;

            var mensaje = form.getAttribute('data-se-confirmar') || mensajeInline(form);
            if (!mensaje) return;

            var boton = e.submitter || null;
            e.preventDefault();
            e.stopImmediatePropagation();

            confirmarModal(opcionesPara(boton, mensaje), function () {
                reproducir(function () { reenviarFormulario(form, boton); });
            });
        }, true);
    }
})();

/* ==========================================================================
   Etapa 8C · Diálogos nativos con formulario o detalle (admin)
   <dialog class="se-modal se-modal--form"> se abre con [data-se-abrir="id"]
   y se cierra con [data-se-cerrar], Esc o clic en el fondo. También expone
   window.seDialogo.abrir(el) / cerrar(el) para las vistas que rellenan datos.
   ========================================================================== */
(function () {
    'use strict';

    var soporta = typeof HTMLDialogElement === 'function';

    function abrir(dialogo) {
        if (!dialogo || dialogo.open) return;
        if (soporta && typeof dialogo.showModal === 'function') dialogo.showModal();
        else dialogo.setAttribute('open', '');
    }

    function cerrar(dialogo) {
        if (!dialogo || !dialogo.open) return;
        if (soporta && typeof dialogo.close === 'function') dialogo.close();
        else dialogo.removeAttribute('open');
    }

    window.seDialogo = { abrir: abrir, cerrar: cerrar };

    document.addEventListener('click', function (e) {
        var el = e.target instanceof Element ? e.target : null;
        if (!el) return;

        var abre = el.closest('[data-se-abrir]');
        if (abre) {
            abrir(document.getElementById(abre.getAttribute('data-se-abrir')));
            return;
        }

        if (el.closest('[data-se-cerrar]')) {
            cerrar(el.closest('dialog'));
            return;
        }

        /* Clic sobre el fondo (::backdrop): el destino es el propio <dialog> */
        if (el.tagName === 'DIALOG' && el.classList.contains('se-modal--form')) {
            var r = el.getBoundingClientRect();
            var fuera = e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
            if (fuera) cerrar(el);
        }
    });
})();
