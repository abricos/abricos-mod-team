/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: '{C#MODNAME}', files: ['member.js']}
	]
};
Component.entryPoint = function(NS){

	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;

	var buildTemplate = this.buildTemplate;
	
	var MemberGroupWidget = function(container, teamid){
		MemberGroupWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, teamid);
	};
	YAHOO.extend(MemberGroupWidget, Brick.mod.widget.Widget, {
		init: function(teamid){
			this.teamid = teamid;
			
			this.team = null;
			this.list = null;
			
			this._editor = null;
			this._wList = [];
		},
		onLoad: function(teamid){
			var __self = this;
			NS.initManager(function(man){
				man.teamLoad(teamid, function(team){
					man.memberListLoad(team, function(list){
						__self._onLoadManager(team, list);
					});
				});
			});
		},
		reloadList: function(){
			this.elShow('loading');
			this.elHide('rlwrap');

			var __self = this;
			NS.manager.memberListLoad(this.team, function(list){
				__self._onLoadManager(__self.team, list);
			});
		},
		_onLoadManager: function(team, list){
			this.team = team;
			this.list = list;
			team.sportsmanList = list;
			
			this.elHide('loading,rlwrap,nullitem');
			
			if (L.isNull(team) || L.isNull(list)){
				this.elShow('nullitem');
				return;
			}
			this.elShow('rlwrap');
			this.render();
		},
		_clearWS: function(){
			var ws = this._wList;
			for (var i=0;i<ws.length;i++){
				ws[i].destroy();
			}
			this._wList = [];
		},
		onClick: function(el, tp){
			var ws = this._wList;
			for (var i=0;i<ws.length;i++){
				if (ws[i].onClick(el)){ return true; }
			}

			switch(el.id){
			case tp['bempadd']: this.showEmpEditor(); return true;
			case tp['bdeptadd']: this.showDeptEditor(); return true;
			}
			return false;
		},
		render: function(){
			var team = this.team, list = this.list;
			if (L.isNull(team) || L.isNull(list)){ return; }
			
			this.elSetVisible('btns', team.role.isAdmin);

			// DEBUG
			// this.showEmpEditor();
			
			var __self = this;
			
			this._clearWS();

			var ws = this._wList, elList = this.gel('list');
			team.detail.deptList.foreach(function(dept){
				ws[ws.length] = new NS.DeptRowWidget(elList, team, list, dept, {
					'onReloadList': function(){
						__self.reloadList();
					}
				});
			});

			for (var i=0;i<ws.length;i++){
				ws[i].render();
			}

			this.empListWidget = new NS.SportsmanListWidget(this.gel('emplist'), team, list, {
				'deptid': 0
			});
		},
		closeEditors: function(){
			if (L.isNull(this._editor)){ return; }
			this._editor.destroy();
			this._editor = null;
			this.elShow('btns,list,emplist');
		},
		_loadEditor: function(component, callback){
			this.closeEditors();
			this.componentLoad('{C#MODNAME}', component, callback, {
				'hide': 'bbtns', 'show': 'edloading'
			});
		},
		showDeptEditor: function(deptid){
			deptid = deptid || 0;
			
			var __self = this;
			this._loadEditor('depteditor', function(){
				__self.showDeptEditorMethod(deptid);
			});
		},
		showDeptEditorMethod: function(deptid){
			this.elHide('btns,list,emplist');

			var dept = deptid==0 ? new NS.Dept() : this.team.detail.deptList.get(deptid);
			var __self = this;
			this._editor = new NS.DeptEditorWidget(this.gel('editor'), this.team, dept, function(act){
				__self.closeEditors();
				if (act == 'save'){ __self.render(); }
			});
		},
		showEmpEditor: function(empid){
			var __self = this;
			this._loadEditor('membereditor', function(){
				__self.showEmpEditorMethod();
			});
		},
		showEmpEditorMethod: function(empid){
			empid = empid||0;
			this.elHide('btns,list,emplist');

			var list = this.team.sportsmanList;
			var emp = empid==0 ? new NS.Sportsman(this.team) : list.get(empid);

			var __self = this;
			this._editor = new NS.MemberEditorWidget(this.gel('editor'), this.team, emp, function(act, semp){
				__self.closeEditors();
				if (act == 'save'){
					list.remove(emp.id);
					list.add(semp);
					__self.render(); 
					__self.reloadList();
				}
			});
		}		
	});
	NS.MemberGroupWidget = MemberGroupWidget;

	var DeptRowWidget = function(container, team, list, dept, cfg){
		cfg = L.merge({
			'onReloadList': null
		}, cfg || {});
		DeptRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'row', 'isRowWidget': true
		}, team, list, dept, cfg);
	};
	YAHOO.extend(DeptRowWidget, Brick.mod.widget.Widget, {
		init: function(team, list, dept, cfg){
			this.team = team;
			this.list = list;
			this.dept = dept;
			this.cfg = cfg;
			this._editor = null;
		},
		buildTData: function(team, list, dept){
			return {'tl': dept.title};
		},
		onClick: function(el){
			var tp = this._TId['row'];
			switch(el.id){
			case tp['bempadd']: this.showEmpEditor(); return true;
			case tp['bdeptadd']: this.showDeptEditor(); return true;
			}
			return false;
		},
		render: function(){
			var team = this.team, dept = this.dept;
			
			this.elSetVisible('btns', team.role.isAdmin);
			this.elSetHTML('depttl', dept.title);

			this.empListWidget = new NS.SportsmanListWidget(this.gel('emplist'), team, this.list, {
				'deptid': dept.id
			});
		},
		closeEditors: function(){
			if (L.isNull(this._editor)){ return; }
			this._editor.destroy();
			this._editor = null;
			this.elShow('btns,list,emplist');
		},
		_loadEditor: function(component, callback){
			this.closeEditors();
			this.componentLoad('{C#MODNAME}', component, callback, {
				'hide': 'bbtns', 'show': 'edloading'
			});
		},
		showDeptEditor: function(){
			var __self = this;
			this._loadEditor('depteditor', function(){
				__self.showDeptEditorMethod();
			});
		},
		showDeptEditorMethod: function(){
			this.elHide('btns,list,emplist');

			var __self = this;
			this._editor = new NS.DeptEditorWidget(this.gel('editor'), this.team, this.dept, function(act){
				__self.closeEditors();
				if (act == 'save'){ __self.render(); }
			});
		},
		showEmpEditor: function(empid){
			var __self = this;
			this._loadEditor('membereditor', function(){
				__self.showEmpEditorMethod();
			});
		},
		showEmpEditorMethod: function(empid){
			empid = empid||0;
			this.elHide('btns,list,emplist');

			var emp = empid==0 ? new NS.Sportsman(this.team, {'deptid': this.dept.id}) : this.team.sportsmanList.get(empid);

			var __self = this;
			this._editor = new NS.MemberEditorWidget(this.gel('editor'), this.team, emp, function(act){
				__self.closeEditors();
				if (act == 'save'){ 
					__self.render();
					NS.life(__self.cfg['onReloadList']);
				}
			});
		}
	});
    NS.DeptRowWidget = DeptRowWidget;	
};