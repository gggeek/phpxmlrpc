
(function(root) {

    var bhIndex = null;
    var rootPath = '';
    var treeHtml = '        <ul>                <li data-name="namespace:" class="opened">                    <div style="padding-left:0px" class="hd">                        <span class="glyphicon glyphicon-play"></span><a href=".html">[Global Namespace]</a>                    </div>                    <div class="bd">                                <ul>                <li data-name="class:xmlrpc_client" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="xmlrpc_client.html">xmlrpc_client</a>                    </div>                </li>                            <li data-name="class:xmlrpc_server" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="xmlrpc_server.html">xmlrpc_server</a>                    </div>                </li>                            <li data-name="class:xmlrpcmsg" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="xmlrpcmsg.html">xmlrpcmsg</a>                    </div>                </li>                            <li data-name="class:xmlrpcresp" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="xmlrpcresp.html">xmlrpcresp</a>                    </div>                </li>                            <li data-name="class:xmlrpcval" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="xmlrpcval.html">xmlrpcval</a>                    </div>                </li>                </ul></div>                </li>                            <li data-name="namespace:PhpXmlRpc" class="opened">                    <div style="padding-left:0px" class="hd">                        <span class="glyphicon glyphicon-play"></span><a href="PhpXmlRpc.html">PhpXmlRpc</a>                    </div>                    <div class="bd">                                <ul>                <li data-name="namespace:PhpXmlRpc_Helper" class="opened">                    <div style="padding-left:18px" class="hd">                        <span class="glyphicon glyphicon-play"></span><a href="PhpXmlRpc/Helper.html">Helper</a>                    </div>                    <div class="bd">                                <ul>                <li data-name="class:PhpXmlRpc_Helper_Charset" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="PhpXmlRpc/Helper/Charset.html">Charset</a>                    </div>                </li>                            <li data-name="class:PhpXmlRpc_Helper_Date" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="PhpXmlRpc/Helper/Date.html">Date</a>                    </div>                </li>                            <li data-name="class:PhpXmlRpc_Helper_Http" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="PhpXmlRpc/Helper/Http.html">Http</a>                    </div>                </li>                            <li data-name="class:PhpXmlRpc_Helper_Logger" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="PhpXmlRpc/Helper/Logger.html">Logger</a>                    </div>                </li>                            <li data-name="class:PhpXmlRpc_Helper_XMLParser" >                    <div style="padding-left:44px" class="hd leaf">                        <a href="PhpXmlRpc/Helper/XMLParser.html">XMLParser</a>                    </div>                </li>                </ul></div>                </li>                            <li data-name="class:PhpXmlRpc_Autoloader" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="PhpXmlRpc/Autoloader.html">Autoloader</a>                    </div>                </li>                            <li data-name="class:PhpXmlRpc_Builder" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="PhpXmlRpc/Builder.html">Builder</a>                    </div>                </li>                            <li data-name="class:PhpXmlRpc_Client" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="PhpXmlRpc/Client.html">Client</a>                    </div>                </li>                            <li data-name="class:PhpXmlRpc_Encoder" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="PhpXmlRpc/Encoder.html">Encoder</a>                    </div>                </li>                            <li data-name="class:PhpXmlRpc_PhpXmlRpc" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="PhpXmlRpc/PhpXmlRpc.html">PhpXmlRpc</a>                    </div>                </li>                            <li data-name="class:PhpXmlRpc_Request" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="PhpXmlRpc/Request.html">Request</a>                    </div>                </li>                            <li data-name="class:PhpXmlRpc_Response" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="PhpXmlRpc/Response.html">Response</a>                    </div>                </li>                            <li data-name="class:PhpXmlRpc_Server" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="PhpXmlRpc/Server.html">Server</a>                    </div>                </li>                            <li data-name="class:PhpXmlRpc_Value" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="PhpXmlRpc/Value.html">Value</a>                    </div>                </li>                            <li data-name="class:PhpXmlRpc_Wrapper" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="PhpXmlRpc/Wrapper.html">Wrapper</a>                    </div>                </li>                </ul></div>                </li>                </ul>';

    var searchTypeClasses = {
        'Namespace': 'label-default',
        'Class': 'label-info',
        'Interface': 'label-primary',
        'Trait': 'label-success',
        'Method': 'label-danger',
        '_': 'label-warning'
    };

    var searchIndex = [
                    
            {"type": "Namespace", "link": ".html", "name": "", "doc": "Namespace "},{"type": "Namespace", "link": "PhpXmlRpc.html", "name": "PhpXmlRpc", "doc": "Namespace PhpXmlRpc"},{"type": "Namespace", "link": "PhpXmlRpc/Helper.html", "name": "PhpXmlRpc\\Helper", "doc": "Namespace PhpXmlRpc\\Helper"},
            
            {"type": "Class", "fromName": "PhpXmlRpc", "fromLink": "PhpXmlRpc.html", "link": "PhpXmlRpc/Autoloader.html", "name": "PhpXmlRpc\\Autoloader", "doc": "&quot;In the unlikely event that you are not using Composer to manage class autoloading, here&#039;s an autoloader for this lib.&quot;"},
                                                        {"type": "Method", "fromName": "PhpXmlRpc\\Autoloader", "fromLink": "PhpXmlRpc/Autoloader.html", "link": "PhpXmlRpc/Autoloader.html#method_register", "name": "PhpXmlRpc\\Autoloader::register", "doc": "&quot;Registers PhpXmlRpc\\Autoloader as an SPL autoloader.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Autoloader", "fromLink": "PhpXmlRpc/Autoloader.html", "link": "PhpXmlRpc/Autoloader.html#method_autoload", "name": "PhpXmlRpc\\Autoloader::autoload", "doc": "&quot;Handles autoloading of classes.&quot;"},
            
            {"type": "Class", "fromName": "PhpXmlRpc", "fromLink": "PhpXmlRpc.html", "link": "PhpXmlRpc/Builder.html", "name": "PhpXmlRpc\\Builder", "doc": "&quot;\n&quot;"},
                                                        {"type": "Method", "fromName": "PhpXmlRpc\\Builder", "fromLink": "PhpXmlRpc/Builder.html", "link": "PhpXmlRpc/Builder.html#method_libVersion", "name": "PhpXmlRpc\\Builder::libVersion", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Builder", "fromLink": "PhpXmlRpc/Builder.html", "link": "PhpXmlRpc/Builder.html#method_buildDir", "name": "PhpXmlRpc\\Builder::buildDir", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Builder", "fromLink": "PhpXmlRpc/Builder.html", "link": "PhpXmlRpc/Builder.html#method_workspaceDir", "name": "PhpXmlRpc\\Builder::workspaceDir", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Builder", "fromLink": "PhpXmlRpc/Builder.html", "link": "PhpXmlRpc/Builder.html#method_distDir", "name": "PhpXmlRpc\\Builder::distDir", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Builder", "fromLink": "PhpXmlRpc/Builder.html", "link": "PhpXmlRpc/Builder.html#method_distFiles", "name": "PhpXmlRpc\\Builder::distFiles", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Builder", "fromLink": "PhpXmlRpc/Builder.html", "link": "PhpXmlRpc/Builder.html#method_getOpts", "name": "PhpXmlRpc\\Builder::getOpts", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Builder", "fromLink": "PhpXmlRpc/Builder.html", "link": "PhpXmlRpc/Builder.html#method_tool", "name": "PhpXmlRpc\\Builder::tool", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Builder", "fromLink": "PhpXmlRpc/Builder.html", "link": "PhpXmlRpc/Builder.html#method_option", "name": "PhpXmlRpc\\Builder::option", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Builder", "fromLink": "PhpXmlRpc/Builder.html", "link": "PhpXmlRpc/Builder.html#method_applyXslt", "name": "PhpXmlRpc\\Builder::applyXslt", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Builder", "fromLink": "PhpXmlRpc/Builder.html", "link": "PhpXmlRpc/Builder.html#method_highlightPhpInHtml", "name": "PhpXmlRpc\\Builder::highlightPhpInHtml", "doc": "&quot;\n&quot;"},
            
            {"type": "Class", "fromName": "PhpXmlRpc", "fromLink": "PhpXmlRpc.html", "link": "PhpXmlRpc/Client.html", "name": "PhpXmlRpc\\Client", "doc": "&quot;Used to represent a client of an XML-RPC server.&quot;"},
                                                        {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method___construct", "name": "PhpXmlRpc\\Client::__construct", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method_setDebug", "name": "PhpXmlRpc\\Client::setDebug", "doc": "&quot;Enable\/disable the echoing to screen of the xmlrpc responses received. The default is not no output anything.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method_setCredentials", "name": "PhpXmlRpc\\Client::setCredentials", "doc": "&quot;Sets the username and password for authorizing the client to the server.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method_setCertificate", "name": "PhpXmlRpc\\Client::setCertificate", "doc": "&quot;Set the optional certificate and passphrase used in SSL-enabled communication with a remote server.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method_setCaCertificate", "name": "PhpXmlRpc\\Client::setCaCertificate", "doc": "&quot;Add a CA certificate to verify server with in SSL-enabled communication when SetSSLVerifypeer has been set to TRUE.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method_setKey", "name": "PhpXmlRpc\\Client::setKey", "doc": "&quot;Set attributes for SSL communication: private SSL key.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method_setSSLVerifyPeer", "name": "PhpXmlRpc\\Client::setSSLVerifyPeer", "doc": "&quot;Set attributes for SSL communication: verify the remote host&#039;s SSL certificate, and cause the connection to fail\nif the cert verification fails.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method_setSSLVerifyHost", "name": "PhpXmlRpc\\Client::setSSLVerifyHost", "doc": "&quot;Set attributes for SSL communication: verify the remote host&#039;s SSL certificate&#039;s common name (CN).&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method_setSSLVersion", "name": "PhpXmlRpc\\Client::setSSLVersion", "doc": "&quot;Set attributes for SSL communication: SSL version to use. Best left at 0 (default value ): let cURL decide&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method_setProxy", "name": "PhpXmlRpc\\Client::setProxy", "doc": "&quot;Set proxy info.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method_setAcceptedCompression", "name": "PhpXmlRpc\\Client::setAcceptedCompression", "doc": "&quot;Enables\/disables reception of compressed xmlrpc responses.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method_setRequestCompression", "name": "PhpXmlRpc\\Client::setRequestCompression", "doc": "&quot;Enables\/disables http compression of xmlrpc request.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method_setCookie", "name": "PhpXmlRpc\\Client::setCookie", "doc": "&quot;Adds a cookie to list of cookies that will be sent to server with every further request (useful e.g. for keeping\nsession info outside of the xml-rpc payload).&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method_setCurlOptions", "name": "PhpXmlRpc\\Client::setCurlOptions", "doc": "&quot;Directly set cURL options, for extra flexibility (when in cURL mode).&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method_setUserAgent", "name": "PhpXmlRpc\\Client::setUserAgent", "doc": "&quot;Set user-agent string that will be used by this client instance in http headers sent to the server.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method_send", "name": "PhpXmlRpc\\Client::send", "doc": "&quot;Send an xmlrpc request to the server.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Client", "fromLink": "PhpXmlRpc/Client.html", "link": "PhpXmlRpc/Client.html#method_multicall", "name": "PhpXmlRpc\\Client::multicall", "doc": "&quot;Send an array of requests and return an array of responses.&quot;"},
            
            {"type": "Class", "fromName": "PhpXmlRpc", "fromLink": "PhpXmlRpc.html", "link": "PhpXmlRpc/Encoder.html", "name": "PhpXmlRpc\\Encoder", "doc": "&quot;A helper class to easily convert between Value objects and php native values&quot;"},
                                                        {"type": "Method", "fromName": "PhpXmlRpc\\Encoder", "fromLink": "PhpXmlRpc/Encoder.html", "link": "PhpXmlRpc/Encoder.html#method_decode", "name": "PhpXmlRpc\\Encoder::decode", "doc": "&quot;Takes an xmlrpc value in object format and translates it into native PHP types.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Encoder", "fromLink": "PhpXmlRpc/Encoder.html", "link": "PhpXmlRpc/Encoder.html#method_encode", "name": "PhpXmlRpc\\Encoder::encode", "doc": "&quot;Takes native php types and encodes them into xmlrpc PHP object format.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Encoder", "fromLink": "PhpXmlRpc/Encoder.html", "link": "PhpXmlRpc/Encoder.html#method_decodeXml", "name": "PhpXmlRpc\\Encoder::decodeXml", "doc": "&quot;Convert the xml representation of a method response, method request or single\nxmlrpc value into the appropriate object (a.k.a. deserialize).&quot;"},
            
            {"type": "Class", "fromName": "PhpXmlRpc\\Helper", "fromLink": "PhpXmlRpc/Helper.html", "link": "PhpXmlRpc/Helper/Charset.html", "name": "PhpXmlRpc\\Helper\\Charset", "doc": "&quot;\n&quot;"},
                                                        {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\Charset", "fromLink": "PhpXmlRpc/Helper/Charset.html", "link": "PhpXmlRpc/Helper/Charset.html#method_instance", "name": "PhpXmlRpc\\Helper\\Charset::instance", "doc": "&quot;This class is singleton for performance reasons.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\Charset", "fromLink": "PhpXmlRpc/Helper/Charset.html", "link": "PhpXmlRpc/Helper/Charset.html#method_encodeEntities", "name": "PhpXmlRpc\\Helper\\Charset::encodeEntities", "doc": "&quot;Convert a string to the correct XML representation in a target charset.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\Charset", "fromLink": "PhpXmlRpc/Helper/Charset.html", "link": "PhpXmlRpc/Helper/Charset.html#method_isValidCharset", "name": "PhpXmlRpc\\Helper\\Charset::isValidCharset", "doc": "&quot;Checks if a given charset encoding is present in a list of encodings or\nif it is a valid subset of any encoding in the list.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\Charset", "fromLink": "PhpXmlRpc/Helper/Charset.html", "link": "PhpXmlRpc/Helper/Charset.html#method_getEntities", "name": "PhpXmlRpc\\Helper\\Charset::getEntities", "doc": "&quot;Used only for backwards compatibility&quot;"},
            
            {"type": "Class", "fromName": "PhpXmlRpc\\Helper", "fromLink": "PhpXmlRpc/Helper.html", "link": "PhpXmlRpc/Helper/Date.html", "name": "PhpXmlRpc\\Helper\\Date", "doc": "&quot;\n&quot;"},
                                                        {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\Date", "fromLink": "PhpXmlRpc/Helper/Date.html", "link": "PhpXmlRpc/Helper/Date.html#method_iso8601Encode", "name": "PhpXmlRpc\\Helper\\Date::iso8601Encode", "doc": "&quot;Given a timestamp, return the corresponding ISO8601 encoded string.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\Date", "fromLink": "PhpXmlRpc/Helper/Date.html", "link": "PhpXmlRpc/Helper/Date.html#method_iso8601Decode", "name": "PhpXmlRpc\\Helper\\Date::iso8601Decode", "doc": "&quot;Given an ISO8601 date string, return a timet in the localtime, or UTC.&quot;"},
            
            {"type": "Class", "fromName": "PhpXmlRpc\\Helper", "fromLink": "PhpXmlRpc/Helper.html", "link": "PhpXmlRpc/Helper/Http.html", "name": "PhpXmlRpc\\Helper\\Http", "doc": "&quot;\n&quot;"},
                                                        {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\Http", "fromLink": "PhpXmlRpc/Helper/Http.html", "link": "PhpXmlRpc/Helper/Http.html#method_decodeChunked", "name": "PhpXmlRpc\\Helper\\Http::decodeChunked", "doc": "&quot;Decode a string that is encoded with \&quot;chunked\&quot; transfer encoding as defined in rfc2068 par. 19.4.6\nCode shamelessly stolen from nusoap library by Dietrich Ayala.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\Http", "fromLink": "PhpXmlRpc/Helper/Http.html", "link": "PhpXmlRpc/Helper/Http.html#method_parseResponseHeaders", "name": "PhpXmlRpc\\Helper\\Http::parseResponseHeaders", "doc": "&quot;Parses HTTP an http response headers and separates them from the body.&quot;"},
            
            {"type": "Class", "fromName": "PhpXmlRpc\\Helper", "fromLink": "PhpXmlRpc/Helper.html", "link": "PhpXmlRpc/Helper/Logger.html", "name": "PhpXmlRpc\\Helper\\Logger", "doc": "&quot;\n&quot;"},
                                                        {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\Logger", "fromLink": "PhpXmlRpc/Helper/Logger.html", "link": "PhpXmlRpc/Helper/Logger.html#method_instance", "name": "PhpXmlRpc\\Helper\\Logger::instance", "doc": "&quot;This class is singleton, so that later we can move to DI patterns.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\Logger", "fromLink": "PhpXmlRpc/Helper/Logger.html", "link": "PhpXmlRpc/Helper/Logger.html#method_debugMessage", "name": "PhpXmlRpc\\Helper\\Logger::debugMessage", "doc": "&quot;Echoes a debug message, taking care of escaping it when not in console mode.&quot;"},
            
            {"type": "Class", "fromName": "PhpXmlRpc\\Helper", "fromLink": "PhpXmlRpc/Helper.html", "link": "PhpXmlRpc/Helper/XMLParser.html", "name": "PhpXmlRpc\\Helper\\XMLParser", "doc": "&quot;Deals with parsing the XML.&quot;"},
                                                        {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\XMLParser", "fromLink": "PhpXmlRpc/Helper/XMLParser.html", "link": "PhpXmlRpc/Helper/XMLParser.html#method_xmlrpc_se", "name": "PhpXmlRpc\\Helper\\XMLParser::xmlrpc_se", "doc": "&quot;xml parser handler function for opening element tags.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\XMLParser", "fromLink": "PhpXmlRpc/Helper/XMLParser.html", "link": "PhpXmlRpc/Helper/XMLParser.html#method_xmlrpc_se_any", "name": "PhpXmlRpc\\Helper\\XMLParser::xmlrpc_se_any", "doc": "&quot;Used in decoding xml chunks that might represent single xmlrpc values.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\XMLParser", "fromLink": "PhpXmlRpc/Helper/XMLParser.html", "link": "PhpXmlRpc/Helper/XMLParser.html#method_xmlrpc_ee", "name": "PhpXmlRpc\\Helper\\XMLParser::xmlrpc_ee", "doc": "&quot;xml parser handler function for close element tags.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\XMLParser", "fromLink": "PhpXmlRpc/Helper/XMLParser.html", "link": "PhpXmlRpc/Helper/XMLParser.html#method_xmlrpc_ee_fast", "name": "PhpXmlRpc\\Helper\\XMLParser::xmlrpc_ee_fast", "doc": "&quot;Used in decoding xmlrpc requests\/responses without rebuilding xmlrpc Values.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\XMLParser", "fromLink": "PhpXmlRpc/Helper/XMLParser.html", "link": "PhpXmlRpc/Helper/XMLParser.html#method_xmlrpc_cd", "name": "PhpXmlRpc\\Helper\\XMLParser::xmlrpc_cd", "doc": "&quot;xml parser handler function for character data.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\XMLParser", "fromLink": "PhpXmlRpc/Helper/XMLParser.html", "link": "PhpXmlRpc/Helper/XMLParser.html#method_xmlrpc_dh", "name": "PhpXmlRpc\\Helper\\XMLParser::xmlrpc_dh", "doc": "&quot;xml parser handler function for &#039;other stuff&#039;, ie. not char data or\nelement start\/end tag. In fact it only gets called on unknown entities.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\XMLParser", "fromLink": "PhpXmlRpc/Helper/XMLParser.html", "link": "PhpXmlRpc/Helper/XMLParser.html#method_guessEncoding", "name": "PhpXmlRpc\\Helper\\XMLParser::guessEncoding", "doc": "&quot;xml charset encoding guessing helper function.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Helper\\XMLParser", "fromLink": "PhpXmlRpc/Helper/XMLParser.html", "link": "PhpXmlRpc/Helper/XMLParser.html#method_hasEncoding", "name": "PhpXmlRpc\\Helper\\XMLParser::hasEncoding", "doc": "&quot;Helper function: checks if an xml chunk as a charset declaration (BOM or in the xml declaration)&quot;"},
            
            {"type": "Class", "fromName": "PhpXmlRpc", "fromLink": "PhpXmlRpc.html", "link": "PhpXmlRpc/PhpXmlRpc.html", "name": "PhpXmlRpc\\PhpXmlRpc", "doc": "&quot;Manages global configuration for operation of the library.&quot;"},
                                                        {"type": "Method", "fromName": "PhpXmlRpc\\PhpXmlRpc", "fromLink": "PhpXmlRpc/PhpXmlRpc.html", "link": "PhpXmlRpc/PhpXmlRpc.html#method_exportGlobals", "name": "PhpXmlRpc\\PhpXmlRpc::exportGlobals", "doc": "&quot;A function to be used for compatibility with legacy code: it creates all global variables which used to be declared,\nsuch as library version etc.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\PhpXmlRpc", "fromLink": "PhpXmlRpc/PhpXmlRpc.html", "link": "PhpXmlRpc/PhpXmlRpc.html#method_importGlobals", "name": "PhpXmlRpc\\PhpXmlRpc::importGlobals", "doc": "&quot;A function to be used for compatibility with legacy code: it gets the values of all global variables which used\nto be declared, such as library version etc.&quot;"},
            
            {"type": "Class", "fromName": "PhpXmlRpc", "fromLink": "PhpXmlRpc.html", "link": "PhpXmlRpc/Request.html", "name": "PhpXmlRpc\\Request", "doc": "&quot;This class provides the representation of a request to an XML-RPC server.&quot;"},
                                                        {"type": "Method", "fromName": "PhpXmlRpc\\Request", "fromLink": "PhpXmlRpc/Request.html", "link": "PhpXmlRpc/Request.html#method___construct", "name": "PhpXmlRpc\\Request::__construct", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Request", "fromLink": "PhpXmlRpc/Request.html", "link": "PhpXmlRpc/Request.html#method_xml_header", "name": "PhpXmlRpc\\Request::xml_header", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Request", "fromLink": "PhpXmlRpc/Request.html", "link": "PhpXmlRpc/Request.html#method_xml_footer", "name": "PhpXmlRpc\\Request::xml_footer", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Request", "fromLink": "PhpXmlRpc/Request.html", "link": "PhpXmlRpc/Request.html#method_createPayload", "name": "PhpXmlRpc\\Request::createPayload", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Request", "fromLink": "PhpXmlRpc/Request.html", "link": "PhpXmlRpc/Request.html#method_method", "name": "PhpXmlRpc\\Request::method", "doc": "&quot;Gets\/sets the xmlrpc method to be invoked.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Request", "fromLink": "PhpXmlRpc/Request.html", "link": "PhpXmlRpc/Request.html#method_serialize", "name": "PhpXmlRpc\\Request::serialize", "doc": "&quot;Returns xml representation of the message. XML prologue included.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Request", "fromLink": "PhpXmlRpc/Request.html", "link": "PhpXmlRpc/Request.html#method_addParam", "name": "PhpXmlRpc\\Request::addParam", "doc": "&quot;Add a parameter to the list of parameters to be used upon method invocation.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Request", "fromLink": "PhpXmlRpc/Request.html", "link": "PhpXmlRpc/Request.html#method_getParam", "name": "PhpXmlRpc\\Request::getParam", "doc": "&quot;Returns the nth parameter in the request. The index zero-based.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Request", "fromLink": "PhpXmlRpc/Request.html", "link": "PhpXmlRpc/Request.html#method_getNumParams", "name": "PhpXmlRpc\\Request::getNumParams", "doc": "&quot;Returns the number of parameters in the message.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Request", "fromLink": "PhpXmlRpc/Request.html", "link": "PhpXmlRpc/Request.html#method_parseResponseFile", "name": "PhpXmlRpc\\Request::parseResponseFile", "doc": "&quot;Given an open file handle, read all data available and parse it as an xmlrpc response.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Request", "fromLink": "PhpXmlRpc/Request.html", "link": "PhpXmlRpc/Request.html#method_parseResponse", "name": "PhpXmlRpc\\Request::parseResponse", "doc": "&quot;Parse the xmlrpc response contained in the string $data and return a Response object.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Request", "fromLink": "PhpXmlRpc/Request.html", "link": "PhpXmlRpc/Request.html#method_kindOf", "name": "PhpXmlRpc\\Request::kindOf", "doc": "&quot;Kept the old name even if Request class was renamed, for compatibility.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Request", "fromLink": "PhpXmlRpc/Request.html", "link": "PhpXmlRpc/Request.html#method_setDebug", "name": "PhpXmlRpc\\Request::setDebug", "doc": "&quot;Enables\/disables the echoing to screen of the xmlrpc responses received.&quot;"},
            
            {"type": "Class", "fromName": "PhpXmlRpc", "fromLink": "PhpXmlRpc.html", "link": "PhpXmlRpc/Response.html", "name": "PhpXmlRpc\\Response", "doc": "&quot;This class provides the representation of the response of an XML-RPC server.&quot;"},
                                                        {"type": "Method", "fromName": "PhpXmlRpc\\Response", "fromLink": "PhpXmlRpc/Response.html", "link": "PhpXmlRpc/Response.html#method___construct", "name": "PhpXmlRpc\\Response::__construct", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Response", "fromLink": "PhpXmlRpc/Response.html", "link": "PhpXmlRpc/Response.html#method_faultCode", "name": "PhpXmlRpc\\Response::faultCode", "doc": "&quot;Returns the error code of the response.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Response", "fromLink": "PhpXmlRpc/Response.html", "link": "PhpXmlRpc/Response.html#method_faultString", "name": "PhpXmlRpc\\Response::faultString", "doc": "&quot;Returns the error code of the response.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Response", "fromLink": "PhpXmlRpc/Response.html", "link": "PhpXmlRpc/Response.html#method_value", "name": "PhpXmlRpc\\Response::value", "doc": "&quot;Returns the value received by the server. If the Response&#039;s faultCode is non-zero then the value returned by this\nmethod should not be used (it may not even be an object).&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Response", "fromLink": "PhpXmlRpc/Response.html", "link": "PhpXmlRpc/Response.html#method_cookies", "name": "PhpXmlRpc\\Response::cookies", "doc": "&quot;Returns an array with the cookies received from the server.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Response", "fromLink": "PhpXmlRpc/Response.html", "link": "PhpXmlRpc/Response.html#method_serialize", "name": "PhpXmlRpc\\Response::serialize", "doc": "&quot;Returns xml representation of the response. XML prologue not included.&quot;"},
            
            {"type": "Class", "fromName": "PhpXmlRpc", "fromLink": "PhpXmlRpc.html", "link": "PhpXmlRpc/Server.html", "name": "PhpXmlRpc\\Server", "doc": "&quot;Allows effortless implementation of XML-RPC servers&quot;"},
                                                        {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method___construct", "name": "PhpXmlRpc\\Server::__construct", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method_setDebug", "name": "PhpXmlRpc\\Server::setDebug", "doc": "&quot;Set debug level of server.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method_xmlrpc_debugmsg", "name": "PhpXmlRpc\\Server::xmlrpc_debugmsg", "doc": "&quot;Add a string to the debug info that can be later serialized by the server\nas part of the response message.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method_error_occurred", "name": "PhpXmlRpc\\Server::error_occurred", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method_serializeDebug", "name": "PhpXmlRpc\\Server::serializeDebug", "doc": "&quot;Return a string with the serialized representation of all debug info.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method_service", "name": "PhpXmlRpc\\Server::service", "doc": "&quot;Execute the xmlrpc request, printing the response.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method_add_to_map", "name": "PhpXmlRpc\\Server::add_to_map", "doc": "&quot;Add a method to the dispatch map.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method_parseRequest", "name": "PhpXmlRpc\\Server::parseRequest", "doc": "&quot;Parse an xml chunk containing an xmlrpc request and execute the corresponding\nphp function registered with the server.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method_getSystemDispatchMap", "name": "PhpXmlRpc\\Server::getSystemDispatchMap", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method_getCapabilities", "name": "PhpXmlRpc\\Server::getCapabilities", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method__xmlrpcs_getCapabilities", "name": "PhpXmlRpc\\Server::_xmlrpcs_getCapabilities", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method__xmlrpcs_listMethods", "name": "PhpXmlRpc\\Server::_xmlrpcs_listMethods", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method__xmlrpcs_methodSignature", "name": "PhpXmlRpc\\Server::_xmlrpcs_methodSignature", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method__xmlrpcs_methodHelp", "name": "PhpXmlRpc\\Server::_xmlrpcs_methodHelp", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method__xmlrpcs_multicall_error", "name": "PhpXmlRpc\\Server::_xmlrpcs_multicall_error", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method__xmlrpcs_multicall_do_call", "name": "PhpXmlRpc\\Server::_xmlrpcs_multicall_do_call", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method__xmlrpcs_multicall_do_call_phpvals", "name": "PhpXmlRpc\\Server::_xmlrpcs_multicall_do_call_phpvals", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method__xmlrpcs_multicall", "name": "PhpXmlRpc\\Server::_xmlrpcs_multicall", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Server", "fromLink": "PhpXmlRpc/Server.html", "link": "PhpXmlRpc/Server.html#method__xmlrpcs_errorHandler", "name": "PhpXmlRpc\\Server::_xmlrpcs_errorHandler", "doc": "&quot;Error handler used to track errors that occur during server-side execution of PHP code.&quot;"},
            
            {"type": "Class", "fromName": "PhpXmlRpc", "fromLink": "PhpXmlRpc.html", "link": "PhpXmlRpc/Value.html", "name": "PhpXmlRpc\\Value", "doc": "&quot;This class enables the creation of values for XML-RPC, by encapsulating plain php values.&quot;"},
                                                        {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method___construct", "name": "PhpXmlRpc\\Value::__construct", "doc": "&quot;Build an xmlrpc value.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_addScalar", "name": "PhpXmlRpc\\Value::addScalar", "doc": "&quot;Add a single php value to an xmlrpc value.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_addArray", "name": "PhpXmlRpc\\Value::addArray", "doc": "&quot;Add an array of xmlrpc value objects to an xmlrpc value.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_addStruct", "name": "PhpXmlRpc\\Value::addStruct", "doc": "&quot;Merges an array of named xmlrpc value objects into an xmlrpc value.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_kindOf", "name": "PhpXmlRpc\\Value::kindOf", "doc": "&quot;Returns a string containing either \&quot;struct\&quot;, \&quot;array\&quot;, \&quot;scalar\&quot; or \&quot;undef\&quot;, describing the base type of the value.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_serialize", "name": "PhpXmlRpc\\Value::serialize", "doc": "&quot;Returns the xml representation of the value. XML prologue not included.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_structmemexists", "name": "PhpXmlRpc\\Value::structmemexists", "doc": "&quot;Checks whether a struct member with a given name is present.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_structmem", "name": "PhpXmlRpc\\Value::structmem", "doc": "&quot;Returns the value of a given struct member (an xmlrpc value object in itself).&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_structreset", "name": "PhpXmlRpc\\Value::structreset", "doc": "&quot;Reset internal pointer for xmlrpc values of type struct.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_structeach", "name": "PhpXmlRpc\\Value::structeach", "doc": "&quot;Return next member element for xmlrpc values of type struct.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_scalarval", "name": "PhpXmlRpc\\Value::scalarval", "doc": "&quot;Returns the value of a scalar xmlrpc value (base 64 decoding is automatically handled here)&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_scalartyp", "name": "PhpXmlRpc\\Value::scalartyp", "doc": "&quot;Returns the type of the xmlrpc value.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_arraymem", "name": "PhpXmlRpc\\Value::arraymem", "doc": "&quot;Returns the m-th member of an xmlrpc value of array type.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_arraysize", "name": "PhpXmlRpc\\Value::arraysize", "doc": "&quot;Returns the number of members in an xmlrpc value of array type.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_structsize", "name": "PhpXmlRpc\\Value::structsize", "doc": "&quot;Returns the number of members in an xmlrpc value of struct type.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_count", "name": "PhpXmlRpc\\Value::count", "doc": "&quot;Returns the number of members in an xmlrpc value:\n- 0 for uninitialized values\n- 1 for scalar values\n- the number of elements for struct and array values&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_getIterator", "name": "PhpXmlRpc\\Value::getIterator", "doc": "&quot;Implements the IteratorAggregate interface&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_offsetSet", "name": "PhpXmlRpc\\Value::offsetSet", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_offsetExists", "name": "PhpXmlRpc\\Value::offsetExists", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_offsetUnset", "name": "PhpXmlRpc\\Value::offsetUnset", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Value", "fromLink": "PhpXmlRpc/Value.html", "link": "PhpXmlRpc/Value.html#method_offsetGet", "name": "PhpXmlRpc\\Value::offsetGet", "doc": "&quot;\n&quot;"},
            
            {"type": "Class", "fromName": "PhpXmlRpc", "fromLink": "PhpXmlRpc.html", "link": "PhpXmlRpc/Wrapper.html", "name": "PhpXmlRpc\\Wrapper", "doc": "&quot;PHP-XMLRPC \&quot;wrapper\&quot; class - generate stubs to transparently access xmlrpc methods as php functions and vice-versa.&quot;"},
                                                        {"type": "Method", "fromName": "PhpXmlRpc\\Wrapper", "fromLink": "PhpXmlRpc/Wrapper.html", "link": "PhpXmlRpc/Wrapper.html#method_php2XmlrpcType", "name": "PhpXmlRpc\\Wrapper::php2XmlrpcType", "doc": "&quot;Given a string defining a php type or phpxmlrpc type (loosely defined: strings\naccepted come from javadoc blocks), return corresponding phpxmlrpc type.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Wrapper", "fromLink": "PhpXmlRpc/Wrapper.html", "link": "PhpXmlRpc/Wrapper.html#method_xmlrpc2PhpType", "name": "PhpXmlRpc\\Wrapper::xmlrpc2PhpType", "doc": "&quot;Given a string defining a phpxmlrpc type return the corresponding php type.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Wrapper", "fromLink": "PhpXmlRpc/Wrapper.html", "link": "PhpXmlRpc/Wrapper.html#method_wrapPhpFunction", "name": "PhpXmlRpc\\Wrapper::wrapPhpFunction", "doc": "&quot;Given a user-defined PHP function, create a PHP &#039;wrapper&#039; function that can\nbe exposed as xmlrpc method from an xmlrpc server object and called from remote\nclients (as well as its corresponding signature info).&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Wrapper", "fromLink": "PhpXmlRpc/Wrapper.html", "link": "PhpXmlRpc/Wrapper.html#method_wrapPhpClass", "name": "PhpXmlRpc\\Wrapper::wrapPhpClass", "doc": "&quot;Given a user-defined PHP class or php object, map its methods onto a list of\nPHP &#039;wrapper&#039; functions that can be exposed as xmlrpc methods from an xmlrpc server\nobject and called from remote clients (as well as their corresponding signature info).&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Wrapper", "fromLink": "PhpXmlRpc/Wrapper.html", "link": "PhpXmlRpc/Wrapper.html#method_wrapXmlrpcMethod", "name": "PhpXmlRpc\\Wrapper::wrapXmlrpcMethod", "doc": "&quot;Given an xmlrpc client and a method name, register a php wrapper function\nthat will call it and return results using native php types for both\nparams and results. The generated php function will return a Response\nobject for failed xmlrpc calls.&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Wrapper", "fromLink": "PhpXmlRpc/Wrapper.html", "link": "PhpXmlRpc/Wrapper.html#method_buildWrapMethodSource", "name": "PhpXmlRpc\\Wrapper::buildWrapMethodSource", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "PhpXmlRpc\\Wrapper", "fromLink": "PhpXmlRpc/Wrapper.html", "link": "PhpXmlRpc/Wrapper.html#method_wrapXmlrpcServer", "name": "PhpXmlRpc\\Wrapper::wrapXmlrpcServer", "doc": "&quot;Similar to wrapXmlrpcMethod, but will generate a php class that wraps\nall xmlrpc methods exposed by the remote server as own methods.&quot;"},
            
            {"type": "Class",  "link": "xmlrpc_client.html", "name": "xmlrpc_client", "doc": "&quot;\n&quot;"},
                    
            {"type": "Class",  "link": "xmlrpc_server.html", "name": "xmlrpc_server", "doc": "&quot;\n&quot;"},
                                                        {"type": "Method", "fromName": "xmlrpc_server", "fromLink": "xmlrpc_server.html", "link": "xmlrpc_server.html#method_echoInput", "name": "xmlrpc_server::echoInput", "doc": "&quot;A debugging routine: just echoes back the input packet as a string value&quot;"},
            
            {"type": "Class",  "link": "xmlrpcmsg.html", "name": "xmlrpcmsg", "doc": "&quot;\n&quot;"},
                    
            {"type": "Class",  "link": "xmlrpcresp.html", "name": "xmlrpcresp", "doc": "&quot;\n&quot;"},
                    
            {"type": "Class",  "link": "xmlrpcval.html", "name": "xmlrpcval", "doc": "&quot;\n&quot;"},
                                                        {"type": "Method", "fromName": "xmlrpcval", "fromLink": "xmlrpcval.html", "link": "xmlrpcval.html#method_serializeval", "name": "xmlrpcval::serializeval", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "xmlrpcval", "fromLink": "xmlrpcval.html", "link": "xmlrpcval.html#method_getval", "name": "xmlrpcval::getval", "doc": "&quot;\n&quot;"},
                    {"type": "Method", "fromName": "xmlrpcval", "fromLink": "xmlrpcval.html", "link": "xmlrpcval.html#method_count", "name": "xmlrpcval::count", "doc": "&quot;Returns the number of members in an xmlrpc value:\n- 0 for uninitialized values\n- 1 for scalar values\n- the number of elements for struct and array values&quot;"},
                    {"type": "Method", "fromName": "xmlrpcval", "fromLink": "xmlrpcval.html", "link": "xmlrpcval.html#method_getIterator", "name": "xmlrpcval::getIterator", "doc": "&quot;Implements the IteratorAggregate interface&quot;"},
            
            
                                        // Fix trailing commas in the index
        {}
    ];

    /** Tokenizes strings by namespaces and functions */
    function tokenizer(term) {
        if (!term) {
            return [];
        }

        var tokens = [term];
        var meth = term.indexOf('::');

        // Split tokens into methods if "::" is found.
        if (meth > -1) {
            tokens.push(term.substr(meth + 2));
            term = term.substr(0, meth - 2);
        }

        // Split by namespace or fake namespace.
        if (term.indexOf('\\') > -1) {
            tokens = tokens.concat(term.split('\\'));
        } else if (term.indexOf('_') > 0) {
            tokens = tokens.concat(term.split('_'));
        }

        // Merge in splitting the string by case and return
        tokens = tokens.concat(term.match(/(([A-Z]?[^A-Z]*)|([a-z]?[^a-z]*))/g).slice(0,-1));

        return tokens;
    };

    root.Sami = {
        /**
         * Cleans the provided term. If no term is provided, then one is
         * grabbed from the query string "search" parameter.
         */
        cleanSearchTerm: function(term) {
            // Grab from the query string
            if (typeof term === 'undefined') {
                var name = 'search';
                var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
                var results = regex.exec(location.search);
                if (results === null) {
                    return null;
                }
                term = decodeURIComponent(results[1].replace(/\+/g, " "));
            }

            return term.replace(/<(?:.|\n)*?>/gm, '');
        },

        /** Searches through the index for a given term */
        search: function(term) {
            // Create a new search index if needed
            if (!bhIndex) {
                bhIndex = new Bloodhound({
                    limit: 500,
                    local: searchIndex,
                    datumTokenizer: function (d) {
                        return tokenizer(d.name);
                    },
                    queryTokenizer: Bloodhound.tokenizers.whitespace
                });
                bhIndex.initialize();
            }

            results = [];
            bhIndex.get(term, function(matches) {
                results = matches;
            });

            if (!rootPath) {
                return results;
            }

            // Fix the element links based on the current page depth.
            return $.map(results, function(ele) {
                if (ele.link.indexOf('..') > -1) {
                    return ele;
                }
                ele.link = rootPath + ele.link;
                if (ele.fromLink) {
                    ele.fromLink = rootPath + ele.fromLink;
                }
                return ele;
            });
        },

        /** Get a search class for a specific type */
        getSearchClass: function(type) {
            return searchTypeClasses[type] || searchTypeClasses['_'];
        },

        /** Add the left-nav tree to the site */
        injectApiTree: function(ele) {
            ele.html(treeHtml);
        }
    };

    $(function() {
        // Modify the HTML to work correctly based on the current depth
        rootPath = $('body').attr('data-root-path');
        treeHtml = treeHtml.replace(/href="/g, 'href="' + rootPath);
        Sami.injectApiTree($('#api-tree'));
    });

    return root.Sami;
})(window);

$(function() {

    // Enable the version switcher
    $('#version-switcher').change(function() {
        window.location = $(this).val()
    });

    
        // Toggle left-nav divs on click
        $('#api-tree .hd span').click(function() {
            $(this).parent().parent().toggleClass('opened');
        });

        // Expand the parent namespaces of the current page.
        var expected = $('body').attr('data-name');

        if (expected) {
            // Open the currently selected node and its parents.
            var container = $('#api-tree');
            var node = $('#api-tree li[data-name="' + expected + '"]');
            // Node might not be found when simulating namespaces
            if (node.length > 0) {
                node.addClass('active').addClass('opened');
                node.parents('li').addClass('opened');
                var scrollPos = node.offset().top - container.offset().top + container.scrollTop();
                // Position the item nearer to the top of the screen.
                scrollPos -= 200;
                container.scrollTop(scrollPos);
            }
        }

    
    
        var form = $('#search-form .typeahead');
        form.typeahead({
            hint: true,
            highlight: true,
            minLength: 1
        }, {
            name: 'search',
            displayKey: 'name',
            source: function (q, cb) {
                cb(Sami.search(q));
            }
        });

        // The selection is direct-linked when the user selects a suggestion.
        form.on('typeahead:selected', function(e, suggestion) {
            window.location = suggestion.link;
        });

        // The form is submitted when the user hits enter.
        form.keypress(function (e) {
            if (e.which == 13) {
                $('#search-form').submit();
                return true;
            }
        });

    
});


