var parser = require('./parser.js');
var node_interface = require('./node_interface.js');
var isemail = require('isemail');

var command = {};

command.handle = function(connectionInfo, buffer) {
  return new Promise(function(resolve, reject) {
    let response = [];

    let pushResponse = function(status, parameter) {
      response.push({
        status: status,
        parameter: parameter
      });
    }

    if(connectionInfo.reading_data) {
      let endPos = buffer.indexOf("\r\n.\r\n");
      if(endPos == -1) {
        connectionInfo.append_data_buffer(buffer);
        pushResponse(250, 'Okay');
      }
      else {
        connectionInfo.reading_data = false;
        connectionInfo.append_data_buffer(buffer.substr(0, endPos));
        pushResponse(250, 'Finished');

        node_interface.submitMail(
          connectionInfo.sender,
          connectionInfo.recipients,
          connectionInfo.data
        ).then(function() {
          console.log("[SERVER] Submitted mail successfully");
        }).catch(function(error) {
          console.log("[SERVER] Error submitting mail. Will retry in 60 seconds.");
          console.log(error);

          // Lets retry every minute
          let retryInterval = setInterval(function() {
            node_interface.submitMail(
              connectionInfo.sender,
              connectionInfo.recipients,
              connectionInfo.data
            ).then(function() {
              console.log("[SERVER] Resubmission of mail was successful");
              clearInterval(retryInterval);
            }).catch(function(error) {
              console.log("[SERVER] Resubmissio of mail failed.");
            });
          }, 60000);
        });
      }
    }
    else {
      switch (buffer.substr(0, 4)) {
        case "HELO": {
          pushResponse(250, 'Hello');
          break;
        }

        case "EHLO": {
          pushResponse(250, 'Extended SMTP');
          pushResponse(250, '8BITMIME');
          pushResponse(250, 'SIZE');
          connectionInfo.extended = true;
          break;
        }

        case "MAIL": {
          if(buffer.substr(5, 4) == "FROM") {
            // handle MAIL FROM.

            let addr = parser.parseMailboxParameter(buffer.substr(9));
            if(isemail.validate(addr)) {
              connectionInfo.sender = addr;
              pushResponse(250, 'Okay');
            }
            else {
              pushResponse(501, 'Invalid Address');
            }
          }
          else {
            pushResponse(550, 'Not Implemented');
          }
          break;
        }

        case "RCPT": {
          if(buffer.substr(5, 2) == "TO") {
            // handle RCPT TO.

            let addr = parser.parseMailboxParameter(buffer.substr(7));
            if(isemail.validate(addr)) {
              node_interface.mailboxExists(addr).then(function(address_exists) {
                if(address_exists) {
                  connectionInfo.recipients.push(addr);
                  pushResponse(250, 'Okay');
                }
                else {
                  pushResponse(550, 'Invalid Address');
                }

                resolve(response);
              }).catch(function(error) {
                reject(error);
              });
              return;
            }
            else {
              pushResponse(501, 'Invalid Address');
            }
          }
          else {
            pushResponse(550, 'Not Implemented');
          }

          break;
        }

        case "DATA": {
          pushResponse(354, 'Okay, incoming data.');
          connectionInfo.reading_data = true;
          break;
        }

        case "RSET": {
          connectionInfo = connectionInfo.default;
          pushResponse(250, 'Okay');
          break;
        }

        case "NOOP": {
          pushResponse(250, 'Okay');
          break;
        }

        case "QUIT": {
          pushResponse(250, 'Closing connection');
          break;
        }

        case "VRFY": {
          pushResponse(502, 'Not Implemented');
          break;
        }

        default: {
          // Send a not implemented
          pushResponse(550, 'Not Implemented');
        }
      }
    }

    resolve(response);
  });
};

command.buildPackets = function(responses) {
  let packets = [];

  for(let i = 0; i < responses.length; i++) {
    if(i == responses.length - 1) {
      packets.push(responses[i]['status'] + " " + responses[i]['parameter'] + "\r\n");
    }
    else {
      packets.push(responses[i]['status'] + "-" + responses[i]['parameter'] + "\r\n");
    }
  }

  return packets;
};


module.exports = command;
