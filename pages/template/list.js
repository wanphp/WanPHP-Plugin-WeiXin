import {DataTableFactory, registerDataTable, registerPage} from "@core/Registry";
import Page from '@core/Page';
import Swal from "sweetalert2";
import htmx from "htmx.org";

let pageName = 'wx.message.template';

class TemplateMessagePage extends Page {
  constructor() {
    super(pageName);
  }

  init() {
    this.bindEvents();
  }

  initDataTable() {
    const action = this.$('.table').attr('data-action');
    if (!action) return;
    this.table = DataTableFactory.create(this.$('.table'), {
      stateSave: true,
      serverSide: false,
      searching: false,
      lengthChange: false,
      bInfo: false,
      pageLength: 20,
      ajax: action,
      rowId: 'template_id',
      columns: [
        {
          title: '模板标题', data: "title", render: (title) => {
            return '<a href="javascript:void(0);" class="btn btn-link">' + title + '</a>';
          }
        },
        {title: '一级行业', data: "primary_industry"},
        {title: '二级行业', data: "deputy_industry"},
        {title: '操作', data: null, render: () => this.getTableButtons(['delete'])}
      ],
      drawCallback: (settings) => {
        this.initTooltips(settings.api.table().container());
      }
    });
    this.use(registerDataTable(this.$('.table[id]').attr('id'), this.table));
  }

  bindEvents() {
    this.on('click', '.table a.btn-link', e => {
      const data = this.getTableRowData(e.currentTarget.closest('tr').id);
      Swal.fire({
        title: '模板详情',
        width: 800,
        html: `<p class="text-start">${data.template_id}</p><div class="row row-cols-2 text-start">
      <div class="col"><div class="card"><div class="card-body">${data.content}</div></div></div>
      <div class="col"><div class="card"><div class="card-body">${data.example}</div></div></div>
    </div>`,
        showCloseButton: true,
        showConfirmButton: false
      }).then();
    });
    this.on('click', '#addTemplate',  (e) =>{
      const tpl_id = e.currentTarget.previousElementSibling.value;
      if (!tpl_id) return false;

      htmx.ajax('post', e.currentTarget.dataset.action, {
        values: {template_id: tpl_id},
        headers: {'Hx-Trigger': 'refresh-page'},
        swap: 'none',
        target: e.currentTarget
      }).then();
    })
  }
}

registerPage(pageName, TemplateMessagePage);
