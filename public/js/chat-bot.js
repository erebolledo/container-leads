(function (global) {
    "use strict";
    var modal = false;
    var mainDiv = null;
    var closable = true;
    var fireWelcomeEvent = true;
    var title = "Bot Name";
    var welcomeEventName = 'welcome';
    var requestUrl = '';
    var wakeUpHour = 0;
    var sleepHour = 24;


    function ChatBot(config) {
        /*
         * Se pueden enviar configuraciones para mostrar el chat en modal, si se puede cerrar o enviar
         * el div principal donde se quiere montar la estructura
         */
        modal = config && config.hasOwnProperty('modal') ? config.modal : false;
        mainDiv = config && config.hasOwnProperty('mainDiv') ? config.mainDiv : null;
        closable = config && config.hasOwnProperty('closable') ? config.closable : true;
        fireWelcomeEvent = config && config.hasOwnProperty('fireWelcomeEvent') ? config.fireWelcomeEvent : true;
        welcomeEventName = config && config.hasOwnProperty('welcomeEventName') ? config.fireWelcomeEvent : 'welcome';
        requestUrl = config && config.hasOwnProperty('requestUrl') ? config.requestUrl : null;
        title = config && config.hasOwnProperty('title') ? config.title : "Bot Name";
        wakeUpHour = config && config.hasOwnProperty('wakeUpHour') ? config.wakeUpHour : 0;
        sleepHour = config && config.hasOwnProperty('sleepHour') ? config.sleepHour : 24;
    }

    var ENTER_KEY_CODE = 13; // Se escucha cuando se presiona el enter
    var queryInput, resultDiv;

    var Bot = ChatBot.prototype;

    /**
     *  Metodo que inicializa el bot
     *  Se crea la estructura ppal de html y se invoca el evento de bienvenida. 
     */
    Bot.init = function () {

        var currentHour = new Date().getHours(); //Hora local del usuario que hace la consulta
        if(currentHour >= wakeUpHour && currentHour < sleepHour){
            this.createBaseHtml();

            if (fireWelcomeEvent === 'yes') {
                this.invokeWelcomeEvent();
            } else {
                var responseNode = this.createResponseNode(true);
                this.setResponseOnNode({answer: '¿Continuamos configurando tu empresa?'}, responseNode);
            }
        }
    };

    /**
     * Metodo que crea todo el html necesario para el bot
     */
    Bot.createBaseHtml = function () {
        var chatElement = null;

        /*
         * Se crea el wrapper, el header, la zona de contenido y el footer
         */
        if (modal && !mainDiv) {
            var wrapper = document.createElement('div');
            wrapper.id = 'bot-chat-main-wrapper';
            document.body.appendChild(wrapper);

            chatElement = document.createElement('div');
            chatElement.className = 'bot-chat-main-container';
            wrapper.appendChild(chatElement);

        } else if (!modal && !mainDiv) {


            chatElement = document.createElement('div');
            chatElement.className = 'bot-chat-main-container';
            document.body.appendChild(chatElement);
        }

        var headerNode = document.createElement('div');
        headerNode.className = 'bot-chat-header';
        headerNode.innerHTML = '<div class="bot-chat-header-title">'+title+'</div>';

        if (closable) {
            var closeNode = document.createElement('div');
            closeNode.className = 'bot-chat-close';
            headerNode.appendChild(closeNode);

            closeNode.addEventListener("click", function (event) {
                var chatBody = document.getElementsByClassName("bot-chat-body")[0];

                chatBody.classList.toggle('hide');

                this.classList.toggle('maximize');
            });
        }
        chatElement.appendChild(headerNode);


        var containerNode = document.createElement('div');
        containerNode.className = 'bot-chat-body';
        chatElement.appendChild(containerNode);

        resultDiv = document.createElement('div');
        resultDiv.id = 'bot-chat-result';
        containerNode.appendChild(resultDiv);

        var botFooter = document.createElement('div');
        botFooter.className = 'bot-chat-footer';

        containerNode.appendChild(botFooter);

        /*
         *  Se crea el input y el boton de enviar, sobre estos elementos se agrega el evento de clic
         *  el cual dispara el bot
         */

        var inputNodeContainer = document.createElement('div');
        inputNodeContainer.className = 'bot-chat-input-field';

        var sendNodeContainer = document.createElement('div');
        sendNodeContainer.className = 'bot-chat-send';
        sendNodeContainer.addEventListener("click", function (event) {
            me.queryInputKeyDown(event, me, true)
        });
        botFooter.appendChild(inputNodeContainer);
        botFooter.appendChild(sendNodeContainer);

        queryInput = document.createElement('input');
        queryInput.id = 'bot-chat-query';
        queryInput.placeholder = '¿Te ayudo?';
        queryInput.type = "text";
        inputNodeContainer.appendChild(queryInput);

        var me = this;
        queryInput.addEventListener("keydown", function (event) {
            me.queryInputKeyDown(event, me);
        });

    };

    /**
     * Metodo que envia request con la informacion del usuario
     * 
     * @param string url    url a la cual realizar el request
     * @param string params parametros a enviar
     * 
     * @returns 
     */
    Bot.makeRequest = function (url, params) {

        var http = new XMLHttpRequest();

        http.open("POST", url, true);
        var me = this;

        /*
         * Send the proper header information along with the request
         * Se debe poner el contenttype en application/x-www-form-urlencoded ya que si se 
         * pone json se activa un depricated 
         */
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.withCredentials = true;
        //        http.setRequestHeader("Content-type", "application/json");
        var responseNode = this.createResponseNode(true);

        http.onreadystatechange = function () {//Call a function when the state changes.
            if (http.readyState == 4 && http.status == 200) {

                /*
                 * Se hace parse de la respuesta, si esta se desea enviar en varias secciones debe 
                 * separar por el patron ***, si se encuentra se envia cada sección en un cuadro diferente
                 * si no se envia directamente
                 */
                var result = http.responseText ? JSON.parse(http.responseText) : '';
                var answerArray = result.answer.split("***");

                if (answerArray.length > 1) {

                    /*
                     * Se envía el primer mensaje y cada X segundos se envía las otras partes del mensaje
                     */
                    me.setResponseOnNode({answer: answerArray[0]}, responseNode);

                    for (var i = 1, len = answerArray.length; i < len; i++) {
                        (function (index, totalCycles, result) {
                            setTimeout(function () {
                                var answerOptionsClass = '';
                                var answerOptions = '';
                                var endConversation = '';

                                /*
                                 * Si la respuesta tiene opciones para poner en "botones" éstas se deben enviar
                                 * en el ultimo mensaje, por lo tanto aquí se verifica si es el ultimo mensaje
                                 * y se agrega
                                 */
                                if (index === totalCycles) {
                                    answerOptionsClass = result.answerOptionsClass;
                                    answerOptions = result.answerOptions;
                                    endConversation = result.endConversation;

                                }
                                /*
                                 * Se crea el nodo donde se va a agregar la respuesta
                                 */
                                responseNode = me.createResponseNode(true, true);

                                /*
                                 * se settea en el nodo de respuesta el mensaje
                                 */
                                me.setResponseOnNode({answer: answerArray[index], answerOptions: answerOptions, answerOptionsClass: answerOptionsClass, endConversation: endConversation}, responseNode);

                                /*
                                 * Para que cada mensaje se envie cada X segundos, se debe settear
                                 * en el timeout el indice por la cantidad de segindos, ya que si no
                                 * se hace hace, después de los X segundos se envían todos los mensajes
                                 */
                            }, i * 2000);
                        })(i, answerArray.length - 1, result);

                    }
                } else {
                    me.setResponseOnNode(result, responseNode);
                }
            } else {
                if (http.status != 200) {
                    me.setResponseOnNode(JSON.parse(http.responseText), responseNode);
                }
            }
        }
        http.send(params);
    }

    /**
     * Invoca el mensaje de bienvenida    
     */
    Bot.invokeWelcomeEvent = function () {

        this.makeRequest(requestUrl, 'event='+welcomeEventName);

    };

    /**
     * Escucha el enter o el boton de enviar y envia la informacion a backend
     *  
     * @param  object     event     Envento
     * @param scope       me
     * @param bool        force     Force metodo
     * @param HTMLelement answerDiv Div de respuestas cerradas
     */
    Bot.queryInputKeyDown = function (event, me, force, answerDiv) {


        force = !force ? false : true;

        if (event && event.which !== ENTER_KEY_CODE && !force) {
            return;
        }

        /*
         * Al seleccionar uno de las opciones de respuesta se deben eliminar las otras opciones
         */
        if (answerDiv) {

            resultDiv.removeChild(answerDiv);

        }

        var value = queryInput.value;
        queryInput.value = "";
//        queryInput.className = "visible";

        /*
         * llama el metodo que crea el div con la respuesta del usuario
         */
        me.createQueryNode(value);

        /*
         * se envia el reqeuest con la respuesta del usuario
         */
        me.makeRequest(requestUrl, 'query=' + value);

    };

    /*
     * Crea el div con la repsuesta del usuario
     * 
     * @param string query Input del usuario
     */
    Bot.createQueryNode = function (query) {

        var mainNode = document.createElement('div');
        mainNode.className = "user-answer-container";

        var node = document.createElement('div');
        node.className = "right-align user-answer";
        node.innerHTML = query;

        mainNode.appendChild(node);
        resultDiv.appendChild(mainNode);
    };

    /**
     * Crea el nodo de respuesta
     * 
     * @param bool addWaiting       Agregar waiting
     * @param bool removeBackground True para eliminar el fondo
     */
    Bot.createResponseNode = function (addWaiting, removeBackground) {

        var mainNode = document.createElement('div');
        mainNode.className = "left-align";

        var node = document.createElement('div');
        node.className = "bot-chat-answer";

        if (addWaiting) {
            node.className = "bot-chat-answer bot-chat-waiting";
        }

        if (removeBackground) {
            var currentClass = mainNode.className;
            mainNode.className = currentClass + "  bot-chat-no-background";

        }
        mainNode.appendChild(node);

        resultDiv.appendChild(mainNode);

        /*
         * Cuando se escribe una respuesta se debe enfocar el scroll al fondo del chat
         */
        var scrollDiv = document.getElementById("bot-chat-result");
        scrollDiv.scrollTop = scrollDiv.scrollHeight;

        return node;
    };

    /**
     * Settea la respuesta del servidor en el nodo 
     * 
     * @param object      response Respuesta
     * @param HTMLElement node     Nodo
     */
    Bot.setResponseOnNode = function (response, node) {
        /*
         * Se sobreescribe la clase para eliminar el waiting
         */
        node.className = "bot-chat-answer";
        
        node.innerHTML = response && response.answer ? response.answer : "Lo siento, no he comprendido tu mensaje, inténtalo de nuevo más tarde";

        /*
         * Si el mensaje incluye opciones de respuesta se envian éstas al metodo que las agrega
         */
        if (response && response.answerOptions && response.answerOptions.length > 0) {
            this.createAnswerOptions(response.answerOptions, response.answerOptionsClass);
        }

        /*
         * Cuando se escribe una respuesta se debe enfocar el scroll al fondo del chat
         */
        var scrollDiv = document.getElementById("bot-chat-result");
        scrollDiv.scrollTop = scrollDiv.scrollHeight;

        if (response.endConversation) {
            setTimeout(function () {
                /*
                 * Si el chat se puede minimizar se minimiza cuando se acaba la conversación, si no
                 * se recarga la pagina para que el chat desaparezca
                 */
                var chatCloseButton = document.getElementsByClassName("bot-chat-close")[0];
                if (chatCloseButton) {
                    chatCloseButton.click();
                } else {
                    location.reload();
                }
            }, 4000);
        }
    };

    /**
     * Metodo que crea los botones para las respuestas cerradas
     * 
     * @param object response
     * @param string className Clase para diferenciar respuestas
     */
    Bot.createAnswerOptions = function (response, className) {

//        queryInput.className = 'hidden'; // consultar control de opciones

        var answerField = document.createElement('div');
        answerField.className = 'answers answers' + className;
        var me = this;

        /*
         * Se crea el div que va a incluir las respuestas y por cada opcion de respuesta se
         * crea un boton que se agrega al div ppal, a cada opcion se agrega también el evento 
         * de click
         */
        for (var i = 0, len = response.length; i < len; i++) {
            var optionDiv = document.createElement('div');

            optionDiv.className = 'answerOption ';
            optionDiv.innerHTML = response[i];
            answerField.appendChild(optionDiv);

            optionDiv.addEventListener("click", function () {
                var optionDiv = this;

                queryInput.value = optionDiv.innerHTML;
                me.queryInputKeyDown(null, me, true, answerField);
            });

        }
        var clearFloatField = document.createElement('div');
        clearFloatField.className = 'clearFloat';

        answerField.appendChild(clearFloatField);
        resultDiv.appendChild(answerField);

    };

    global['ChatBot'] = ChatBot;
}(this));



