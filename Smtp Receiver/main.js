var net = require('net');
var config = require('./config/config.js');
var command = require('./library/command.js');

process.on('uncaughtException', function(error) {

});

process.on('unhandledRejection', function(reason, p) {

});

var smtpServer = net.createServer();

smtpServer.on('connection', function(clientSock) {
  // Logging connection
  console.log("[SERVER] Connection from " + clientSock.remoteAddress + ", on port " + clientSock.remotePort + ", over " + clientSock.remoteFamily);
  clientSock.setEncoding('utf8');

  // Variable for storing information with this specific connection
  let connectionInfo = {
    // Clent sock..
    sock: clientSock,

    // the size of data (aka the mail object)
    size: 0,

    // The expected size of the mail body.
    expected_size: 0,

    // The recipients
    recipients: [],
    sender: '',

    // Is this extended-smtp, or just smtp.
    extended: true,

    // Is this content content after the DATA command?
    reading_data: false,

    // The received mail object.
    data: '',

    append_data_buffer: function(buffer) {
      if(connectionInfo.size + buffer.Length > config.max_mail_size) {
        Error("Exceeded maximum mail size");
      }

      if(connectionInfo.expected_size > config.max_mail_size) {
        Error("Expected size is too large.");
      }

      connectionInfo.size += buffer.Length;
      connectionInfo.data += buffer.toString();
    }
  };
  connectionInfo.default = connectionInfo;

  // On received data command
  clientSock.on('data', function(data) {
    console.log("C: " + data.substr(0, data.length - 2));
    command.handle(connectionInfo, data).then(function(commandResult) {
      let packets = command.buildPackets(commandResult);
      for (let i = 0; i < packets.length; i++) {
        clientSock.write(packets[i], 'utf8', function() {
          console.log("S: " + packets[i].substr(0, packets[i].length - 2));
        });
      }
    }).catch(function(error) {
      console.log(error);
      clientSock.end();
    });
  });

  clientSock.on('end', function() {
    console.log("[SERVER] Connection with " + clientSock.remoteAddress + " has been ended.");
  });

  clientSock.on('error', function(err) {
    console.log("[SERVER] An error has occured with " + clientSock.remoteAddress);
    console.log(err);
  });

  clientSock.on('timeout', function() {
    console.log("[SERVER] " + clientSock.remoteAddress + " has timed out.");
  });

  // server saying hello to client.
  let packets = command.buildPackets([{
    status: 220,
    parameter: "<" + config.smtp.hostname + ">"
  }]);
  for (let i = 0; i < packets.length; i++) {
    clientSock.write(packets[i]);
  }
});

smtpServer.listen(config.smtp.port, function() {
  console.log(new Date());
  console.log("[SERVER] Started Listeer. Listening on port " + config.smtp.port);
});
