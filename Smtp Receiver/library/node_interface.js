var config = require('../config/config.js');
var url = require('url');
var http = require('http');

var node_interface = {};

node_interface.mailboxExists = function(mailbox)
{
  return new Promise(function(resolve, reject) {
    let postData = JSON.stringify({
      address: mailbox
    });

    let reqOptions = url.parse(config.node.buildTemplate('mb_exists'));
    reqOptions['method'] = 'POST';
    reqOptions['headers'] = {
      'Content-Length': Buffer.byteLength(postData),
      'Content-Type': 'text/json',
      'User-agent': 'SmtpReceiverNode/0.1',
      'Host': reqOptions['hostname']
    };

    let request = http.request(reqOptions, function(res) {
      let data = ''
      res.on('data', function(chunk) { data += chunk; });

      res.on('end', function() {
        if(res.statusCode == 200) {
          let responseParsed = JSON.parse(data);
          if(responseParsed['success']) {
            resolve(responseParsed['data']['exists']);
          }
          else {
            reject(Error("Success returned false"));
          }
        }
        else {
          reject(Error('Invalid status code. ' + res.statusCode));
        }
      });
    });

    request.write(postData);
    request.end();
  });
};

node_interface.submitMail = function(sender, recipients, mailObject)
{
  return new Promise(function(resolve, reject) {
    let postData = JSON.stringify({
      sender: sender,
      receivers: recipients,
      mail: Buffer.from(mailObject).toString('base64')
    });


    let reqOptions = url.parse(config.node.buildTemplate('mb_new'));
    reqOptions['method'] = 'POST';
    reqOptions['headers'] = {
      'Content-Length': Buffer.byteLength(postData),
      'Content-Type': 'text/json',
      'User-agent': 'SmtpReceiverNode/0.1',
      'Host': reqOptions['hostname']
    };

    let request = http.request(reqOptions, function(res) {
      let data = '';
      res.on('data', function(chunk) { data += chunk; });

      res.on('end', function() {
        if(res.statusCode == 200) {
          let responseParsed = JSON.parse(data);
          if(responseParsed['success']) {
            resolve();
          }
          else {
            reject(Error('Server returned false on success'));
          }
        }
        else {
          reject(Error('Error status code'));
        }
      });
    });

    request.write(postData);
    request.end();
  });
};

module.exports = node_interface;
