(function() {
  app.controller("settings", [
    "$scope",
    "$rootScope",
    "http",
    "$stateParams",
    "Tip",
    function(s, rs, http, $stateParams, tip) {
      s.map = {
        pay_out_limit: {
          name: "出款限额上限：",
          type: "text",
          dataType: "INT",
          maxLength: 10,
          status: "default"
        }
      };
      s.list = [];
      s.getData = function() {
        http.get("/admin/settings", {}, function(res) {
          if (res.status == 0) {
            s.list = res.data;
          } else {
            tip.error("网络错误");
          }
        });
      };

      s.changeStatus = function(status, item) {
        if (s.map[item.key]) s.map[item.key].status = status;
      };

      s.check = function(item) {
        switch (item.key) {
          case "pay_out_limit":
            if (!item.val) {
              tip.error("请输入出款限额上限!");
              return false;
            }
            break;
        }
        return true;
      };
      s.save = function(item) {
        http.post(
          "/admin/updateSetting/" + item.id,
          { id: item.id, val: item.val },
          function(res) {
            if (res.status == 0) {
              s.map[item.key].status = "default";
              tip.success("保存成功");
              s.getData();
            } else {
              tip.error("网络错误");
            }
          }
        );
      };

      s.getData();
    }
  ]);
})();
