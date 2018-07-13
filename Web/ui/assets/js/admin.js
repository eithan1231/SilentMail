var admin = new function() {
  this.possibleUserList = { };

  this.handleUserWildcardSearch = function(e) {
    let dropdownElement = document.getElementById('admin-user-query-list');

    if(e.code == "Enter") {
      if(this.possibleUserList !== false) {
        this.showUserControlPage(
          this.possibleUserList[0]['id'],
          this.possibleUserList[0]['username']
        );
      }
    }
    else {
      let username = document.getElementById('admin-search-user').value;
      if(username.length < 1) {
        return;
      }
      let url = Library.getPostRoute("admin-query-user");
      let postData = JSON.stringify({
        user: username,
        wildcard: true
      });
      Library.doAjax(url, function(status, content) {
        if(status === 200) {
          let responseParsed = JSON.parse(content);
          if(responseParsed['success']) {
            admin.possibleUserList = responseParsed['data'];

            dropdownElement.innerHTML = '';
            for (let i = 0; i < responseParsed['data'].length; i++) {
              let user = responseParsed['data'][i];

              let userSpanContainer = document.createElement("div");
              let userSpan = document.createElement("a");
              userSpan.href = "javascript://";
              userSpan.classList.add("clean-a");
              userSpan.style.color = user['group_color'];
              userSpan.style.fontWeight = "bold";
              userSpan.style.fontSize = "10px";
              userSpan.innerHTML += Library.escapeHtml(user['username']);
              userSpan.addEventListener('click', function() {
                admin.showUserControlPage(user['id'], user['username']);
                return false;
              });

              userSpanContainer.appendChild(userSpan);
              dropdownElement.appendChild(userSpanContainer);
            }
          }
          else if(typeof responseParsed['data']['message'] !== 'undefined') {
            Library.alert(responseParsed['data']['message']);
          }
        }
        else {
          Library.alert("Server Error: Server returned " + status.toString() + ", should be 200.");
        }
      }, "POST", postData);
    }
  }

  this.showUserControlPage = function(user_id, username = null) {
    let query = "user_id=" + encodeURIComponent(user_id);
    if(username !== null) {
      // We dont query the username, we just use the userid. This is just here
      // for debugging purposes... Having the username in the url helped debug,
      // a lot.
      query += "&username=" + encodeURIComponent(username);
    }
    Tab.changeTab('template-admin-user-manage', false, main_page_id, query);
  }
};
