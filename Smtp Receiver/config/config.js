var config = {};

config.smtp = {};
config.general = {};
config.node = {};

// General confugurations
config.max_mail_size = 1024 * 1024 * 64;// 64 mb.

// Information about the smtp.
config.smtp.port = 25;
config.smtp.hostname = "mx1.eithan.me";

// Info for this node to authenticate to web server.
config.node.key = "laiklumhyslaiklumhyslaiklumhyslaiklumhyslaiklumhyslaiklumhys1212";
config.node.template = "http://localhost/node/" + config.node.key + "/{action}";
config.node.buildTemplate = function(action) {
  return config.node.template.replace('{action}', action);
};

module.exports = config;
