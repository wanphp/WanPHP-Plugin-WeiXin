import {registerPage, registerDataTable, DataTableFactory} from "@core/Registry";
import Page from '@core/Page';
import Swal from "sweetalert2";

let pageName = 'wx.auto_reply';

class AutoReplyPage extends Page {
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

    this.table = DataTableFactory.create(this.$('.table'), {
      responsive: false,
      autoWidth: true,
      scrollX: true,
      ajax: action,
      rowId: 'id',
      columns: [
        {
          title: '接收消息类型', className: "align-middle", data: "msgType", render: function (data) {
            const map = {
              click: '点击菜单',
              view: '点击菜单跳转URL',
              subscribe: '粉丝关注',
              text: '接收到文本',
              image: '接收到图片',
              voice: '接收到语音',
              video: '接收到视频',
              shortvideo: '接收到小视频',
              location: '接收到地理位置',
              link: '接收到链接'
            };

            return map[data] || data || '-';
          }
        },
        {title: '关键词', className: "align-middle", data: "key"},
        {
          title: '回复信息类型', className: "align-middle", data: "replyType", render: function (data) {
            const map = {
              image: '图片',
              voice: '语音',
              video: '视频',
              text: '文本消息',
              music: '音乐',
              news: '图文消息'
            };

            return map[data] || data || '-';
          }
        },
        {
          title: '回复内容', className: "align-middle", data: "view",
        },
        {
          data: null,
          className: "align-middle",
          render: () => this.getTableButtons(['edit', 'delete'])
        }
      ]
    });
    this.use(registerDataTable(this.$('.table[id]').attr('id'), this.table));
  }

  bindEvents() {
    this.on('click', '.table .image', e => {
      Swal.fire({
        imageUrl: e.target.src,
        customClass: {
          image: 'm-0 img-fluid rounded shadow-lg'
        },
        width: 'auto',
        imageHeight: '500',
        padding: '0',
        showConfirmButton: false
      }).then();
    });
    this.on('click', '.table .music', e => {
      Swal.fire({
        title: e.currentTarget.dataset.name,
        html: `<audio src="${e.currentTarget.dataset.url}" controls autoplay style="width:100%;"></audio>`,
        showCloseButton: true,
        showConfirmButton: false
      }).then();
    });
    this.on('click', '.table .video', e => {
      Swal.fire({
        title: '视频解析中...',
        allowOutsideClick: false, // 禁止点击外部关闭
        allowEscapeKey: false,    // 禁止 Esc 键关闭
        showConfirmButton: false, // 隐藏确认按钮
        didOpen: () => {
          Swal.showLoading();     // 显示加载动画
        }
      });
      fetch(e.currentTarget.dataset.url)
        .then(r => r.json())
        .then(res => {
          if (!res.url) {
            Swal.fire('无法预览', '视频解析失败', 'error');
            return;
          }

          Swal.fire({
            title: e.currentTarget.dataset.title,
            html: `<video src="${res.url}" controls autoplay playsinline style="width:100%;max-height:70vh;background:#000"></video>`,
            showCloseButton: true,
            showConfirmButton: false,
            // 恢复允许点击外部关闭（Swal默认就是true，这里通常不需要显式写，除非你想特殊处理）
            allowOutsideClick: true
          });
        });
    });
  }
}

registerPage(pageName, AutoReplyPage);
