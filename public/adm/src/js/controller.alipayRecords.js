(function() {
  app.controller("alipayRecords", [
    "$scope",
    "$rootScope",
    "Table",
    "http",
    "ngDialog",
    "Tip",
    function(s, rs, Table, http, ngDialog, tip) {
      s.table = Table;
      s.setting = {
        isEdit: false,
        placeholder: "会员账户、银行账号",
        currentTable: "transfer",
        query: {}
      };
      s.dict = { transfer: false, personal: false, merchant: false },s.temp={};

      s.ptable = angular.copy(s.table);
      s.mtable = angular.copy(s.table);
      s.gtable = angular.copy(s.table);

      s.displayTable = function(name) {
        s.setting.currentTable = name;
        switch (name) {
          case "personal":
            s.setting.query = s.ptable.query;
            s.setting.placeholder = "二维码编号、会员账户";
            break;
          case "merchant":
            s.setting.query = s.mtable.query;
            s.setting.placeholder = "会员账户，单号，商户编码，商户名称";
            break;
          case "transfer":
            s.setting.query = s.gtable.query;
            s.setting.placeholder = "会员账户、银行账号";
            break;
        }
      };

      s.search = function() {
        switch (s.setting.currentTable) {
          case "personal":
            s.ptable.query = $.extend(s.ptable.query, s.setting.query);
            s.ptable.getList(1);
            break;
          case "merchant":
            s.mtable.query = $.extend(s.mtable.query, s.setting.query);
            s.mtable.getList(1);
            break;
          case "transfer":
            s.gtable.query = $.extend(s.gtable.query, s.setting.query);
            s.gtable.getList(1);
            break;
        }
      };

      s.act = function(list, n, type) {
        var apiUrl, params;
        if (type == "personal") {
          apiUrl = "/admin/updatePaySingleState/" + list.id;
          params = { id: list.id, status: n };
        } else if (type == "merchant") {
          apiUrl = "/admin/updateOrder/" + list.id;
          params = { id: list.id, status: n };
        } else if (type == "transfer") {
          apiUrl = "/admin/updateOfflinePayStatus/" + list.id;
          params = { id: list.id, status: n, type: 2 };
        }
        ngDialog
          .open({
            template:
              '<div class="confirm-dialog"> \
              <h2>您确定要将此记录标记为' +
              ["已入款", "已忽略"][n - 1] +
              '吗？</h2>\
              <div align="center">\
                  <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                  <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
              </div></div>',
            plain: true
          })
          .closePromise.then(function(data) {
            if (data.value && data.value == "CONFIRM") {
              http.post(apiUrl, params, function(res) {
                if (res.status == 0) {
                  tip.success("操作成功!");
                  s.search(type);
                } else {
                  tip.error(res.msg);
                }
              });
            }
          });
      };

      s.beginEdit = function() {
        s.setting.isEdit = true;
        s.temp = angular.copy(s.dict);
      };

      s.cancelEdit = function(){
        s.setting.isEdit = false;
        s.dict = angular.copy(s.temp);;
      }

      s.updateAliSetting = function() {
        var arr = [];
        if (s.dict.transfer) arr.push(1);
        if (s.dict.personal) arr.push(2);
        if (s.dict.merchant) arr.push(3);
        http.post(
          "/admin/updateSetting/" + 7,
          { id: 7, val: arr.join(",") },
          function(res) {
            if (res.status == 0) {
              tip.success("修改成功!");
              s.setting.isEdit = false;
            } else {
              tip.error("网络错误");
            }
          }
        );
      };

      s.getData = function() {
        http.get("/getAliPay", {}, function(res) {
          if (res.status == 0 && res.data[0]) {
            if (res.data[0].val.indexOf("1") != -1) s.dict.transfer = true;
            if (res.data[0].val.indexOf("2") != -1) s.dict.personal = true;
            if (res.data[0].val.indexOf("3") != -1) s.dict.merchant = true;
          } else {
            tip.error("网络错误");
          }
        });
      };

      s.getData();

      s.ptable
        .init({ link: "/admin/paySingles", query: { type: 2 } })
        .getList();
      s.mtable
        .init({ link: "/admin/PayMerchants", query: { type: 2 } })
        .getList();
      s.gtable
        .init({ link: "/admin/offlinePays", query: { type: 2 } })
        .getList();
    }
  ]);
})();
