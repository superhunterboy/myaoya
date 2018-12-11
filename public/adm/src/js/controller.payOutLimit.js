(function() {
  app.controller("editpayOutLimit", [
    "$scope",
    "http",
    function(s, http) {
      s.limit = {};
      s.levels = [];
      if (s.ngDialogData.limit) {
        s.id = s.ngDialogData.limit.id;
        s.limit.id = s.ngDialogData.limit.id;
        s.limit.count = s.ngDialogData.limit.count;
        if (s.ngDialogData.limit.level_ids.length > 0) {
          s.ngDialogData.limit.level_ids.forEach(function(level) {
            s.levels.push(level);
            console.log(level);
          });
          console.log(s.levels);
        }
      }
      http.get("/admin/getAvailableMemberLevels", { id: s.id }, function(res) {
        s.levelList = res;
        if (s.levels.length > 0) {
          s.levelList.forEach(function(level) {
            level.checked = s.levels.indexOf(level.id + "") > -1;
          });
        }
      });
      s.getLevelIds = function() {
        var levels = [];
        s.levelList.forEach(function(level) {
          if (level.checked) {
            levels.push(level.id);
          }
        });
        return levels.join(",");
      };
      s.check = function() {
        s.limit.levelIds = s.getLevelIds();

        if (!s.limit.levelIds) {
          s.error = true;
          s.errorMsg = "请选择层级";
          return false;
        }
        return true;
      };

      s.sub = function() {
        var url;
        if (!s.id) {
          url = "/admin/addPayOutLimit";
        } else {
          url = "/admin/editPayOutLimit/" + s.id;
        }

        if (!s.loading && s.check()) {
          s.loading = true;
          http.post(url, s.limit, function(res) {
            s.loading = false;
            if (res.status == 0) {
              s.closeThisDialog(res);
            } else {
              s.error = true;
              s.errorMsg = res.msg;
            }
          });
        }
      };
    }
  ]);

  app.controller("payOutLimit", [
    "$scope",
    "$rootScope",
    "Table",
    "http",
    "ngDialog",
    function(s, rs, Table, http, ngDialog) {
      s.table = Table.init({ link: "/admin/getPayOutLimit" });
      s.table.getList();

      s.delLimit = function(list, n) {
        ngDialog
          .open({
            template:
              '<div class="confirm-dialog"> \
                <h2>确定要删除此列表信息？</h2>\
                <div align="center">\
                    <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                    <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
                </div></div>',
            plain: true
          })
          .closePromise.then(function(data) {
            if (data.value && data.value == "CONFIRM") {
              http.get("/admin/deletePayOutLimit/" + list.id, {}, function(
                res
              ) {
                if (res.status == 0) {
                  s.table.getList();
                }
              });
            }
          });
      };

      s.editLimit = function(limit) {
        ngDialog
          .open({
            template: "template/payOutLimitDialog.html",
            controller: "editpayOutLimit",
            data: { limit: limit }
          })
          .closePromise.then(function(data) {
            if (data.value && data.value.status == 0) {
              s.table.getList();
            }
          });
      };
    }
  ]);
})();
