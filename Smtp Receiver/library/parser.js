var parser = {};

parser.parseMailboxParameter = function(parameter)
{
  let opening = parameter.indexOf('<');
  let closing = parameter.lastIndexOf('>');

  if(opening == -1 || closing == -1) {
    return '';
  }

  let addressLength = closing - opening - 1;
  let address = parameter.substr(opening + 1, addressLength);

  return address;
};

module.exports = parser;
