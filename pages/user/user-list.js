import {registerPage, registerDataTable, DataTableFactory} from "@core/Registry";
import Page from '@core/Page';
import htmx from "htmx.org";

let pageName = 'wx.user.list';

class UserListPage extends Page {
  constructor() {
    super(pageName);
  }

  init() {
    this.bindEvents();
  }

  destroy() {
  }

  initDataTable() {
    const action = this.$('.table').attr('data-action');
    if (!action) return;
    const userTags = {};
    Array.from(this.root.querySelectorAll('#tags .btn:not([data-id=""])')).forEach(btn => {
      userTags[btn.dataset.id] = btn.textContent.trim();
    });
    this.table = DataTableFactory.create(this.$('.table'), {
      ajax: action,
      rowId: 'openid',
      columns: [
        {
          title: '微信',
          data: "avatar", render: function (data, type, row) {
            let html = '';
            if (userTags) {
              html = '<div class="btn-group dropup">' +
                '<button type="button" class="btn btn-sm dropdown-toggle btn-link text-body-secondary" ' +
                'data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
              if (row.tag_id_list && row.tag_id_list.length > 0) {
                for (const tag_id of row.tag_id_list) {
                  html += '<span class="me-2">' + userTags[tag_id] + '</span>';
                }
              } else {
                html += '<span>无标签</span>';
              }
              html += '</button><div class="dropdown-menu p-2">';
              for (const id in userTags) {
                html += '<div class="form-check form-check-inline"><label class="form-check-label"><input class="form-check-input" type="checkbox"  value="' + id + '"';
                //if (row.tagid_list) console.log(row.tagid_list,id, row.tagid_list.includes(parseInt(id)));
                if (row.tag_id_list) html += (row.tag_id_list.includes(parseInt(id)) ? ' checked' : '');
                html += '>' + userTags[id] + '</label></div>';
              }
              html += '</div></div>';
            }
            if (data) return '<img src="' + data + '" class="img-thumbnail" style="padding:0;" width="30" alt="头像">' + row.nickname + html;
            else return row.openid;
          }
        },
        {title: '姓名', data: "name"},
        {title: '手机号', data: "tel"},
        {
          title: '关注时间', data: "createdAt", render: this.formatDateTime
        },
        {
          title: '操作',
          width: 80,
          data: "op",
          defaultContent: '<button type="button" class="btn btn-tool edit" data-toggle="tooltip" title="修改用户信息"><i class="fas fa-edit"></i></button>' +
            '<button type="button" class="btn btn-tool subscribe" data-toggle="tooltip" title="更新关注信息"><i class="fas fa-sync-alt"></i></button>'
        }
      ]
    });
    this.use(registerDataTable(this.$('.table[id]').attr('id'), this.table));
    let syncing = false;

    this.table.instance.on('xhr.dt', (e, settings, json) => {
      if (json?.recordsTotal === 0 && json?.syncFollowers && !syncing) {
        syncing = true;
        showLoading('粉丝同步中...');
        const fd = {};

        async function syncFollowers(next_openid) {
          fd.next_openid = next_openid;
          const res = await fetch(json.syncFollowers, {
            method: 'POST',
            body: JSON.stringify(fd),
            headers: {
              "Content-Type": "application/json",
            }
          });
          const data = await res.json();

          if (data?.next_openid) {
            const progress = ((data.sync_total / data.user_total) * 100).toFixed(2);
            fd.sync_total = data.sync_total;
            Swal.update({
              title: '粉丝同步' + progress + '%'
            });
            Swal.showLoading();
            return syncFollowers(data.next_openid);
          }
          return data;
        }

        syncFollowers('').then(() => {
          Swal.fire({
            icon: 'success',
            title: '同步完成！',
            showConfirmButton: false,
            timer: 1500
          });
          this.table.reload();
        });
      }
      if (json?.syncFollowersInfo) {
        syncing = true;
        showLoading('粉丝基本信息同步中...');
        const fd = {user_info: 'sync', sync_total: json.sync_total, user_total: json.recordsTotal};

        async function syncFollowersInfo() {
          const res = await fetch(json.syncFollowersInfo, {
            method: 'POST',
            body: JSON.stringify(fd),
            headers: {
              "Content-Type": "application/json",
            }
          });
          const data = await res.json();

          if (data?.syncFollowers === 100) {
            const progress = ((data.sync_total / data.user_total) * 100).toFixed(2);
            fd.sync_total = data.sync_total;
            fd.user_total = data.user_total;
            Swal.update({
              title: '粉丝基本信息同步' + progress + '%'
            });
            Swal.showLoading();
            return syncFollowersInfo();
          }
          return data;
        }

        syncFollowersInfo().then(() => {
          Swal.fire({
            icon: 'success',
            title: '同步完成！',
            showConfirmButton: false,
            timer: 1500
          });
          this.table.reload();
        });
      }
    });
  }

  bindEvents() {
    this.on('click', '#tags button[data-id]', (e) => {
      const $btn = this.$(e.currentTarget);
      const tagId = e.currentTarget.dataset.id;

      if ($btn.hasClass('btn-outline-info')) {
        this.$('#tags button[data-id]').removeClass('btn-outline-success').addClass('btn-outline-info');
        $btn.removeClass('btn-outline-info').addClass('btn-outline-success');

        const currentUrl = new URL(this.table.instance.ajax.url(), window.location.origin);

        if (tagId) {
          currentUrl.searchParams.set('tag_id', tagId);
        } else {
          currentUrl.searchParams.delete('tag_id');
        }

        this.table.instance.ajax.url(currentUrl.pathname + currentUrl.search).load();
      }
    });
    this.on('click', '#setCookie', (e) => {
      Swal.fire({
        title: "设置公众号Token，Cookie",
        html: `
    <div class="mb-3 form-group">
      <input id="token" type="number" class="form-control" placeholder="微信公号后台URL地址中的token值" autocomplete="off" value="">
    </div>
    <div class="mb-3 form-group">
      <textarea id="cookies" class="form-control" rows="5" placeholder="在微信公号后台，按F12，刷新网页，切换到Network选项卡，点击请求地址，在Request Headers中复制cookie值粘贴到此处" autocomplete="off"></textarea>
    </div>
  `,
        focusConfirm: false,
        preConfirm: () => {
          const result = {token: $("#token").val(), cookies: $("#cookies").val()}
          if (!result.token) return Swal.showValidationMessage("未获取Token值!");
          if (!result.cookies) return Swal.showValidationMessage("未获取Cookie值!");
          if (result.cookies.indexOf('data_ticket') === -1) return Swal.showValidationMessage("cookie不正确!");
          return result;
        },
        showCancelButton: true,
        confirmButtonText: "提交",
        cancelButtonText: "取消",
        showLoaderOnConfirm: true,
      }).then((result) => {
        if (result.isConfirmed) {
          htmx.ajax('post', e.currentTarget.dataset.action, {
            values: result.value,
            swap: 'none',
            target: e.currentTarget
          }).then();
        }
      });
    })
    let syncing = false;
    this.on('click', '#syncFollowers', (e) => {
      const action = e.currentTarget.dataset.action;
      if (!syncing && action) {
        syncing = true;
        showLoading('粉丝头像、呢称同步中...');
        const fd = {user_info: 'sync', sync_total: '', user_total: ''};

        async function syncFollowersInfo() {
          const res = await fetch(action, {
            method: 'POST',
            body: JSON.stringify(fd),
            headers: {
              "Content-Type": "application/json",
            }
          });
          const data = await res.json();

          if (data?.syncFollowers === 20) {
            const progress = ((data.sync_total / data.user_total) * 100).toFixed(2);
            fd.sync_total = data.sync_total;
            fd.user_total = data.user_total;
            Swal.update({
              title: '粉丝头像、呢称同步' + progress + '%'
            });
            Swal.showLoading();
            return syncFollowersInfo();
          }
          return data;
        }

        syncFollowersInfo().then(() => {
          Swal.fire({
            icon: 'success',
            title: '同步完成！',
            showConfirmButton: false,
            timer: 1500
          });
          this.table.reload();
        });
      }
    });
    this.on('click', 'table tbody .dropdown-menu input', (e) => {
      const row = this.table.instance.row('#' + e.target.closest('tr').id);
      const user = row.data();
      const tagId = e.target.value;
      if (e.target.checked) {
        if (!user.tag_id_list || !user.tag_id_list.includes(tagId)) {
          // 添加标签
          htmx.ajax('patch', user.tag_action, {
            values: {tagId: tagId},
            swap: 'none',
            target: e.currentTarget
          }).then();
        }
      } else {
        // 删除标签
        htmx.ajax('delete', user.tag_action, {
          values: {tagId: tagId},
          swap: 'none',
          target: e.currentTarget
        }).then();
      }
    });
    this.on('click', '.table tbody .edit', (e) => {
      const data = this.getTableRowData(e.target.closest('tr').id);

      Swal.fire({
        title: "用户信息",
        html: `
    <form id="swal-form" action="${data.update}" method="POST" class="needs-validation" novalidate>
      <div class="input-group mb-3">
        <label for="name" class="input-group-text">姓名</label>
        <input id="name" name="name" value="${data.name}" type="text" class="form-control" required
               pattern="[\\u4e00-\\u9fa5]{2,8}$" placeholder="用户姓名" autocomplete="off">
      </div>
      <div class="input-group mb-3">
        <label for="tel" class="input-group-text">手机号</label>
        <input id="tel" name="tel" value="${data.tel}" type="text" class="form-control" required
               pattern="^(13[0-9]|14[579]|15[0-35-9]|16[6]|17[0-9]|18[0-9]|19[12589])\\d{8}$"
               placeholder="用户手机号" autocomplete="off">
      </div>
      <div class="input-group mb-3">
        <label for="remark" class="input-group-text">备注</label>
        <input id="remark" name="remark" value="${data.remark}" type="text" class="form-control" placeholder="用户备注"
               autocomplete="off">
      </div>
      <div class="input-group">
        <label for="status" class="input-group-text">状态</label>
        <select id="status" name="status" class="form-select">
          <option value="0" ${data.status === 0 ? 'selected' : ''}>正常</option>
          <option value="1" ${data.status === 1 ? 'selected' : ''}>锁定</option>
        </select>
      </div>
    </form>
  `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: "提交",
        cancelButtonText: "取消",
        showLoaderOnConfirm: true,
        preConfirm: () => {
          const form = document.getElementById('swal-form');

          if (!form.checkValidity()) {
            form.classList.add('was-validated');
            Swal.showValidationMessage('请检查表单填写是否正确');
            return false;
          }

          return Object.fromEntries(new FormData(form).entries());
        }
      }).then((result) => {
        if (result.isConfirmed) {
          htmx.ajax('post', data.update, {
            values: result.value,
            headers: {'Hx-Trigger': 'refresh-page'},
            swap: 'none',
            target: e.target
          }).then();
        }
      });
    });
    this.on('click', '.table .subscribe', e => {
      const data = this.table.instance.row('#' + e.target.closest('tr').id).data();
      htmx.ajax('get', data.update, {
        headers: {'Hx-Trigger': 'refresh-page'},
        swap: 'none',
        target: e.target
      }).then();
    });
  }
}

registerPage(pageName, UserListPage);
