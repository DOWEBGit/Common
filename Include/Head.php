<style>
    .loaderContainer {
        position: fixed;
        top: 0;
        bottom: 0;
        width: 100%;
        background-color: rgba(0, 0, 0, 0.05);
        z-index: 1000;
    }

    .loader {
        position: fixed;
        z-index: 1000;
        margin: auto;
        border: 5px solid #EAF0F6 !important;
        border-radius: 50% !important;
        border-top: 5px solid #FF7A59;
        width: 100px;
        height: 100px;
        animation: spinner 4s linear infinite;
        top: calc(50% - 50px);
        left: calc(50% - 50px);
    }

    @keyframes spinner {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>

<!--suppress JSDeprecatedSymbols -->
<script>
    const createLock = () =>
    {
        let lockStatus = false
        const release = () =>
        {
            lockStatus = false
        }
        const acuire = () =>
        {
            if (lockStatus === true)
                return false
            lockStatus = true
            return true
        }
        return {
            lockStatus: lockStatus,
            acuire: acuire,
            release: release,
        }
    }
    lock = createLock(); // create a lock
    var globalReload = 0;
    var loader = null;
    var loaderContainer = null;

    function ReloadViewAll(preload = 200)
    {
        ReloadViewBefore();

        ShowLoader(preload);

        var elementi = document.querySelectorAll('[id^="View"]');
        globalReload = elementi.length;
        for (var i = 0; i < elementi.length; i++)
        {
            let viewId = elementi[i].id.replace(/view/gi, ""); //case sensitive
            ReloadViewInternal(viewId);
        }
    }

    function ReloadViewBefore()
    {

    }

    function ReloadViewCompleted()
    {

    }

    function ReloadView(viewName, preload = 200)
    {
        ReloadViewBefore();

        ShowLoader(preload);

        let fullName = "View\\" + viewName;

        let view;

        var elementi = document.querySelectorAll('[id^="View"]');

        for (var i = 0; i < elementi.length; i++)
        {
            let viewTmp = elementi[i];

            let name = viewTmp.getAttribute("view");

            if (name !== fullName)
                continue;

            view = viewTmp;

            break;
        }

        if (typeof view === 'undefined')
        {
            console.log("Non trovo la view con nome " + viewName);
            return;
        }

        let viewId = elementi[i].id.replace(/view/gi, ""); //case sensitive

        ReloadViewInternal(viewId);
    }

    function ReloadViewInternal(viewId)
    {
        const call = async () =>
        {
            var divName = "View" + viewId;
            var oldObj = document.getElementById(divName);
            if (oldObj == null)
            {
                console.log("Non trovo la view nel div " + divName);
                return;
            }

            let viewTag = oldObj.getAttribute("view");

            if (viewTag == null)
            {
                console.log("Non trovo il tag view nel div " + divName);
                return;
            }

            var hidden = document.getElementById("WindowState");
            let array = [];
            array.push(""); //tempState
            if (hidden)
            {
                try
                {
                    array.push(hidden.value);
                }
                catch (e)
                {
                }
            }

            const response = await fetch('/Public/Php/Common/View/Client.php?view=' + viewTag,
            {
                method: 'POST',
                headers:
                    {
                        'Content-Type': 'application/json'
                    },
                body: JSON.stringify(array)
            });

            let json = await response.text();
            let htmlState = JSON.parse(json);

            //prendo l'html
            oldObj.innerHTML = htmlState[0];

            let jsonArray = "";

            try
            {
                jsonArray = JSON.parse(htmlState[1]);
            }
            catch
            {
                console.log(htmlState[1]);
                return;
            }

            document.getElementById("TempState").value = jsonArray[0];
            document.getElementById("WindowState").value = jsonArray[1];

            globalReload--;
            if (globalReload <= 0)
            {
                ReloadViewCompleted();
                HideLoader();
            }
        }

        call();
    }

    function Push(nome, valore)
    {
        if (typeof window[nome] === "function")
        {
            if (valore === undefined || valore === null)
            {
                window[nome]();
            } else
            {
                try
                {
                    // Prova a convertire la stringa JSON in un array
                    var arrayValore = JSON.parse(valore);

                    if (Array.isArray(arrayValore))
                    {
                        window[nome].apply(null, arrayValore);
                    } else
                    {
                        window[nome](valore); // Se non è un array, passa come singolo parametro
                    }
                } catch (error)
                {
                    window[nome](valore); // Se c'è un errore, passa come singolo parametro
                }
            }
        } else
        {
            console.error("PUSH: La funzione " + nome + " non esiste o non è una funzione.");
        }
    }

    let timeoutPreloader;

    function ShowLoader(preload)
    {
        if (loaderContainer != null || loader != null)
            return;

        if (preload < 0)
            preload = 0;

        clearTimeout(timeoutPreloader);

        timeoutPreloader = setTimeout(function ()
        {
            loaderContainer = document.createElement("div");
            loaderContainer.classList.add("loaderContainer");

            loader = document.createElement("div");
            loader.classList.add("loader");

            loaderContainer.appendChild(loader);

            document.body.appendChild(loaderContainer);

        }, preload);
    }

    function HideLoader()
    {
        clearTimeout(timeoutPreloader);

        if (loaderContainer == null || loader == null)
            return;

        loaderContainer.remove();

        loaderContainer = null;
        loader = null;
    }

    function Action(controller, action, result, keepAlive = false)
    {
        if (!lock.acuire()) // acuired a lock
            return;
        var windowState = document.getElementById("WindowState");
        var windowJson = "";
        if (windowState)
        {
            try
            {
                windowJson = windowState.value;
            } catch (e)
            {
            }
        }
        var tempState = document.getElementById("TempState");
        var tempJson = "";
        if (tempState)
        {
            try
            {
                tempJson = tempState.value;
            } catch (e)
            {
            }
        }
        let finalArray = [tempJson, windowJson];
        fetch('/Public/Php/Common/View/Client.php?controller=' + controller + '&action=' + action,
            {
                method: 'POST',
                keepalive: keepAlive, //The keepalive option can be used to allow the request to outlive the page.
                headers:
                    {
                        'Content-Type': 'application/json'
                    },
                body: JSON.stringify(finalArray)
            })
            .then(data =>
            {
                data.text().then(output =>
                {
                    let jsonArray = "";

                    try
                    {
                        jsonArray = JSON.parse(output);
                    }
                    catch
                    {
                        console.log(output);
                        return;
                    }

                    document.getElementById("TempState").value = jsonArray[0];
                    document.getElementById("WindowState").value = jsonArray[1];
                    lock.release();

                    if (typeof result === "function")
                        result();
                });
            })
            .catch(error =>
            {
                console.log('Network error:', error);
            });
    }

    function WindowWrite(name, value)
    {
        if (typeof name === "undefined")
        {
            console.log("WindowWrite: name è undefined");
            return;
        }
        if (typeof value === "undefined")
        {
            console.log("WindowWrite: value è undefined");
            return;
        }
        name = name.toLowerCase();

        let windowState = document.getElementById("WindowState");
        var json = windowState.value;
        var jsonArray = {};
        if (json)
        {
            try
            {
                json = decodeURIComponent(escape(atob(json)));
                jsonArray = JSON.parse(json);
            }
            catch (e)
            {
            }
        }
        jsonArray[name] = value.toString();
        json = JSON.stringify(jsonArray);
        windowState.value = btoa(unescape(encodeURIComponent(json)));
    }

    function WindowRead(name)
    {
        if (typeof name === "undefined")
        {
            console.log("WindowRead: name è undefined");
            return;
        }
        name = name.toLowerCase();
        var json = document.getElementById("WindowState").value;
        var jsonArray = [];
        if (json)
        {
            try
            {
                json = decodeURIComponent(escape(atob(json)));
                jsonArray = JSON.parse(json);
            } catch (e)
            {
            }
        }
        let res = jsonArray[name];
        if (typeof res === 'undefined')
            return "";
        return res;
    }

    function TempClear()
    {
        document.getElementById("TempState").value = "";
    }

    function TempWrite(name, value)
    {
        if (typeof name === "undefined")
        {
            console.log("TempWrite: name è undefined");
            return;
        }
        if (typeof value === "undefined")
        {
            console.log("TempWrite: value è undefined");
            return;
        }
        name = name.toLowerCase();
        var json = document.getElementById("TempState").value;
        var jsonArray = {};
        if (json)
        {
            try
            {
                json = decodeURIComponent(escape(atob(json)));
                jsonArray = JSON.parse(json);
            } catch (e)
            {
                console.log(e);
            }
        }
        jsonArray[name] = value.toString();
        json = JSON.stringify(jsonArray);
        json = btoa(unescape(encodeURIComponent(json)));
        document.getElementById("TempState").value = json;
    }

    function TempRead(name)
    {
        if (typeof name === "undefined")
        {
            console.log("TempRead: name è undefined");
            return;
        }
        name = name.toLowerCase();
        var json = document.getElementById("TempState").value;
        var jsonArray = [];
        if (json)
        {
            try
            {
                json = decodeURIComponent(escape(atob(json)));
                jsonArray = JSON.parse(json);
            } catch (e)
            {
                return e;
            }
        }
        let res = jsonArray[name];
        if (typeof res === 'undefined')
            return "";
        return res;
    }

    function TempWriteAllId(separator = "-")
    {
        var postInputs = document.querySelectorAll('input[class*="TempData"], textarea[class*="TempData"], input[type="checkbox"][class*="TempData"], select[class*="TempData"]');
        postInputs.forEach(function (input)
        {
            var id = input.id;
            var value;
            if (input.type === 'checkbox')
            {
                value = input.checked ? 'true' : 'false';
            } else if (input.tagName.toLowerCase() === 'select')
            {
                if (input.multiple)
                {
                    value = "";

                    /*acchiappo tutte i valori e ci combino un array */
                    const selected = document.querySelectorAll('#'+input.id+' option:checked');
                    const values = Array.from(selected).map(el => el.value);

                    values.forEach((item) => {

                        value += item + separator;
                    });

                    //tolgo l'ultimo '-'
                    if (value !== "")
                        value = value.slice(0, -1);

                }
                else
                {
                    var selectedOption = input.options[input.selectedIndex];
                    value = selectedOption.value;
                }

            } else
            {
                value = input.value;
            }

            TempWrite(id, value);
        });
    }

    function getOffset(el) {
        const rect = el.getBoundingClientRect();
        return {
            left: rect.left + window.scrollX,
            top: rect.top + window.scrollY
        };
    }

    function TempReadAllId(message = "", scroll = false)
    {
        var postInputs = document.querySelectorAll('input[class*="TempData"], textarea[class*="TempData"], input[type="checkbox"][class*="TempData"], select[class*="TempData"]');

        var labelDanger = []

        postInputs.forEach(function (input)
        {
            let id = input.id;
            let value = TempRead(id);
            let label = document.querySelector('label[for="' + id + '"]');
            if (value.trim() !== '')
            {
                if (!label)
                {
                    label = document.createElement('label');
                    label.setAttribute('for', id);
                    label.classList.add('danger');
                    input.parentNode.insertBefore(label, input.nextSibling);

                }
                label.innerHTML = value;

                labelDanger.push(label);

            } else if (label)
            {
                label.parentNode.removeChild(label);
            }
        });

        if (message !== '')
        {
            let parser = new DOMParser();
            alert(parser.parseFromString(message, 'text/html').documentElement.textContent);
        }

        if (scroll)
        {
            //altezza di default, non faccio una mazza se quando arrivo in fondo il valore è ancora questo
            let labelHeight = -1;

            labelDanger.forEach(function (label)
            {
                let input = document.getElementById(label.attributes.getNamedItem("for").value);

                let top = getOffset(input).top - 10;

                if ((labelHeight == -1) || (labelHeight > top))
                    labelHeight = top;
            });

            if (labelHeight !== -1)
                window.scrollTo(0, labelHeight);
        }
    }

    function TempReadAllIdWithSpan(message = "", scroll = false)
    {
        var postInputs = document.querySelectorAll('input[class*="TempData"], textarea[class*="TempData"], input[type="checkbox"][class*="TempData"], select[class*="TempData"]');

        var labelDanger = []

        postInputs.forEach(function (input)
        {
            let id = input.id;
            let value = TempRead(id);
            let label = document.querySelector('span[id="Errore' + id + '"]');
            if (value.trim() !== '')
            {
                if (!label)
                {
                    label = document.createElement('span');
                    label.setAttribute('id', "Errore" + id);
                    label.classList.add('danger');
                    input.parentNode.insertBefore(label, input.nextSibling);

                }
                label.innerHTML = value;

                labelDanger.push(label);

            } else if (label)
            {
                label.parentNode.removeChild(label);
            }
        });

        if (message !== '')
        {
            let parser = new DOMParser();
            alert(parser.parseFromString(message, 'text/html').documentElement.textContent);
        }

        if (scroll)
        {
            //altezza di default, non faccio una mazza se quando arrivo in fondo il valore è ancora questo
            let labelHeight = -1;

            labelDanger.forEach(function (label)
            {
                let input = document.getElementById(label.attributes.getNamedItem("for").value);

                let top = getOffset(input).top - 10;

                if ((labelHeight == -1) || (labelHeight > top))
                    labelHeight = top;
            });

            if (labelHeight !== -1)
                window.scrollTo(0, labelHeight);
        }
    }

    // Funzione per salvare lo stato dei valori degli input
    function WindowWriteAllId(separator = "-")
    {
        var postInputs = document.querySelectorAll('input[class*="TempData"], textarea[class*="TempData"], input[type="checkbox"][class*="TempData"], select[class*="TempData"]');
        postInputs.forEach(function (input)
        {
            var id = input.id;
            var value;
            if (input.type === 'checkbox')
            {
                value = input.checked ? 'true' : 'false';
            } else if (input.tagName.toLowerCase() === 'select')
            {
                if (input.multiple)
                {
                    value = "";

                    /*acchiappo tutte i valori e ci combino un array */
                    const selected = document.querySelectorAll('#'+input.id+' option:checked');
                    const values = Array.from(selected).map(el => el.value);

                    values.forEach((item) => {

                        value += item + separator;
                    });

                    //tolgo l'ultimo '-'
                    if (value !== "")
                        value = value.slice(0, -1);

                }
                else
                {
                    var selectedOption = input.options[input.selectedIndex];
                    value = selectedOption.value;
                }

            } else
            {
                value = input.value;
            }

            WindowWrite(id, value);
        });
    }

    function WindowReadAllId(message = "", scroll = false)
    {
        var postInputs = document.querySelectorAll('input[class*="TempData"], textarea[class*="TempData"], input[type="checkbox"][class*="TempData"], select[class*="TempData"]');

        var labelDanger = []

        postInputs.forEach(function (input)
        {
            let id = input.id;
            let value = TempRead(id);
            let label = document.querySelector('label[for="' + id + '"]');
            if (value.trim() !== '')
            {
                if (!label)
                {
                    label = document.createElement('label');
                    label.setAttribute('for', id);
                    label.classList.add('danger');
                    input.parentNode.insertBefore(label, input.nextSibling);

                }
                label.innerHTML = value;

                labelDanger.push(label);

            } else if (label)
            {
                label.parentNode.removeChild(label);
            }
        });

        if (message !== '')
        {
            let parser = new DOMParser();
            alert(parser.parseFromString(message, 'text/html').documentElement.textContent);
        }

        if (scroll)
        {
            //altezza di default, non faccio una mazza se quando arrivo in fondo il valore è ancora questo
            let labelHeight = -1;

            labelDanger.forEach(function (label)
            {
                let input = document.getElementById(label.attributes.getNamedItem("for").value);

                let top = getOffset(input).top - 10;

                if ((labelHeight == -1) || (labelHeight > top))
                    labelHeight = top;
            });

            if (labelHeight !== -1)
                window.scrollTo(0, labelHeight);
        }
    }

    function WindowReadAllIdWithSpan(message = "", scroll = false)
    {
        var postInputs = document.querySelectorAll('input[class*="TempData"], textarea[class*="TempData"], input[type="checkbox"][class*="TempData"], select[class*="TempData"]');

        var labelDanger = []

        postInputs.forEach(function (input)
        {
            let id = input.id;
            let value = TempRead(id);
            let label = document.querySelector('span[id="Errore' + id + '"]');
            if (value.trim() !== '')
            {
                if (!label)
                {
                    label = document.createElement('span');
                    label.setAttribute('id', "Errore" + id);
                    label.classList.add('danger');
                    input.parentNode.insertBefore(label, input.nextSibling);

                }
                label.innerHTML = value;

                labelDanger.push(label);

            } else if (label)
            {
                label.parentNode.removeChild(label);
            }
        });

        if (message !== '')
        {
            let parser = new DOMParser();
            alert(parser.parseFromString(message, 'text/html').documentElement.textContent);
        }

        if (scroll)
        {
            //altezza di default, non faccio una mazza se quando arrivo in fondo il valore è ancora questo
            let labelHeight = -1;

            labelDanger.forEach(function (label)
            {
                let input = document.getElementById(label.attributes.getNamedItem("for").value);

                let top = getOffset(input).top - 10;

                if ((labelHeight == -1) || (labelHeight > top))
                    labelHeight = top;
            });

            if (labelHeight !== -1)
                window.scrollTo(0, labelHeight);
        }
    }
</script>