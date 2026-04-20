import {registerPage} from "@core/Registry";
import Page from '@core/Page';
import htmx from "htmx.org";
import Swal from "sweetalert2";

let pageName = 'wx.user.tags';

class UserTagsPage extends Page {
  constructor() {
    super(pageName);
  }

  init() {
    this.bindEvents();
  }

  destroy() {
  }

  bindEvents() {
    this.on('click', '#addTag', e => {
      Swal.fire({
        title: "添加粉丝标签",
        input: "text",
        showCancelButton: true,
        confirmButtonText: '提交',
        cancelButtonText: '取消',
        inputValidator: (value) => {
          if (value === '') {
            return "请输入标签名称!";
          }
        }
      }).then((result) => {
        if (result.isConfirmed) {
          htmx.ajax('post', e.currentTarget.dataset.action, {
            values: {name: result.value},
            headers: {'Hx-Trigger': 'refresh-page'},
            swap: 'none',
            target: e.target
          }).then();
        }
      });
    });
    this.on('click', '.table .edit', e => {
      Swal.fire({
        title: "修改粉丝标签",
        input: "text",
        inputValue: e.currentTarget.dataset.name,
        showCancelButton: true,
        confirmButtonText: '提交',
        cancelButtonText: '取消',
        inputValidator: (value) => {
          if (value === '') {
            return "请输入标签名称!";
          }
        }
      }).then((result) => {
        if (result.isConfirmed) {
          htmx.ajax('post', e.currentTarget.dataset.action, {
            values: {name: result.value},
            headers: {'Hx-Trigger': 'refresh-page'},
            swap: 'none',
            target: e.target
          }).then();
        }
      });
    });
    this.on('click', '.table .delete', e => {
      window.confirmDialog('是否确认要删除粉丝标签', () => {
        htmx.ajax('delete', e.currentTarget.dataset.action, {
          headers: {'Hx-Trigger': 'refresh-page'},
          swap: 'none',
          target: e.target
        }).then();
      });
    });
  }
}

registerPage(pageName, UserTagsPage);
