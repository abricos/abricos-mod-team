/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: '{C#MODNAME}', files: ['memberview.js']}
	]
};
Component.entryPoint = function(NS){

	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;

	var buildTemplate = this.buildTemplate;
	
	var MemberGroupListWidget = function(container, teamid, cfg){
		cfg = L.merge({
			'modName': 'team',
			'sEditorWidget': 'MemberGroupEditorWidget',
			'sEditorComponent': 'mgroupeditor',
			'sEditorModule': 'team',
			'sMemberEditorWidget': 'MemberEditorWidget',
			'sMemberEditorComponent': 'membereditor',
			'sMemberEditorModule': 'team',
			'act': '',
			'param': ''
		}, cfg || {});

		MemberGroupListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, teamid, cfg);
	};
	YAHOO.extend(MemberGroupListWidget, Brick.mod.widget.Widget, {
		init: function(teamid, cfg){
			this.teamid = teamid;
			this.cfg = cfg;
			
			this.team = null;
			this.list = null;
			
			this._editor = null;
			this._wList = [];
		},
		onLoad: function(teamid, cfg){
			var __self = this;
			Brick.mod[cfg['modName']].initManager(function(man){
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
			case tp['bempadd']: this.showMemberEditor(); return true;
			case tp['bgroupadd']: this.showMemberGroupEditor(); return true;
			}
			return false;
		},
		render: function(){
			var team = this.team, list = this.list;
			if (L.isNull(team) || L.isNull(list)){ return; }
			
			this.elSetVisible('btns', team.role.isAdmin);

			var __self = this;
			
			this._clearWS();

			var ws = this._wList, elList = this.gel('list');
			team.memberGroupList.foreach(function(group){
				ws[ws.length] = new NS.MemberGroupRowWidget(elList, team, list, group, {
					'onReloadList': function(){
						__self.reloadList();
					}
				});
			});

			for (var i=0;i<ws.length;i++){
				ws[i].render();
			}

			this.memberListWidget = new NS.MemberListWidget(this.gel('emplist'), team, list, {
				'groupid': 0
			});
		},
		closeEditors: function(){
			if (L.isNull(this._editor)){ return; }
			this._editor.destroy();
			this._editor = null;
			this.elShow('btns,list,emplist');
		},
		
		showMemberGroupEditor: function(groupid){
			groupid = groupid || 0;
			this.closeEditors();
			var __self = this, cfg = this.cfg;
			
			this.componentLoad(cfg['sEditorModule'], cfg['sEditorComponent'], function(){
				__self.showMemberGroupEditorMethod(groupid);
			}, {'hide': 'bbtns', 'show': 'edloading'});
		},
		showMemberGroupEditorMethod: function(groupid){
			this.elHide('btns,list,emplist');

			var group = groupid==0 ? new NS.MemberGroup() : this.team.memberGroupList.get(groupid);
			var __self = this, cfg = this.cfg, team = this.team;
			
			this._editor = new Brick.mod[cfg['sEditorModule']][cfg['sEditorWidget']](this.gel('editor'), team, group, function(act){
				__self.closeEditors();
				if (act == 'save'){ __self.render(); }
			});
		},
		showMemberEditor: function(memberid){
			this.closeEditors();
			
			var __self = this, cfg = this.cfg;

			this.componentLoad(cfg['sMemberEditorModule'], cfg['sMemberEditorComponent'], function(){
				__self.showMemberEditorMethod(memberid);
			}, {'hide': 'bbtns', 'show': 'edloading'});
		},
		showMemberEditorMethod: function(memberid){
			memberid = memberid||0;
			this.elHide('btns,list,view');
			
			var __self = this, cfg = this.cfg, team = this.team,
				member = memberid==0 ? new team.manager.MemberClass(team, dList[i]) : list.get(memberid);
			this._editor = new Brick.mod[cfg['sMemberEditorModule']][cfg['sMemberEditorWidget']](this.gel('editor'), team, member, function(act, newMember){
				__self.closeEditors();
				if (act == 'save'){
					if (L.isValue(member)){
						list.remove(member.id);
						list.add(newMember);
						__self.render(); 
						__self.reloadList();
					}
					__self.renderDetail();
				}
			});
		}
	});
	NS.MemberGroupListWidget = MemberGroupListWidget;

	var MemberGroupRowWidget = function(container, team, list, group, cfg){
		cfg = L.merge({
			'onReloadList': null
		}, cfg || {});
		MemberGroupRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'row', 'isRowWidget': true
		}, team, list, group, cfg);
	};
	YAHOO.extend(MemberGroupRowWidget, Brick.mod.widget.Widget, {
		init: function(team, list, group, cfg){
			this.team = team;
			this.list = list;
			this.group = group;
			this.cfg = cfg;
			this._editor = null;
		},
		buildTData: function(team, list, group){
			return {'tl': group.title};
		},
		onClick: function(el){
			var tp = this._TId['row'];
			switch(el.id){
			case tp['bempadd']: this.showMemberEditor(); return true;
			case tp['bgroupadd']: this.showMemberGroupEditor(); return true;
			}
			return false;
		},
		render: function(){
			var team = this.team, group = this.group;
			
			this.elSetVisible('btns', team.role.isAdmin);
			this.elSetHTML('grouptl', group.title);

			this.memberListWidget = new NS.MemberListWidget(this.gel('emplist'), team, this.list, {
				'groupid': group.id
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
		showMemberGroupEditor: function(){
			var __self = this;
			this._loadEditor('groupeditor', function(){
				__self.showMemberGroupEditorMethod();
			});
		},
		showMemberGroupEditorMethod: function(){
			this.elHide('btns,list,emplist');

			var __self = this;
			this._editor = new NS.DeptEditorWidget(this.gel('editor'), this.team, this.group, function(act){
				__self.closeEditors();
				if (act == 'save'){ __self.render(); }
			});
		},
		showMemberEditor: function(memberid){
			var __self = this;
			this._loadEditor('membereditor', function(){
				__self.showMemberEditorMethod();
			});
		},
		showMemberEditorMethod: function(memberid){
			memberid = memberid||0;
			this.elHide('btns,list,emplist');

			var emp = memberid==0 ? new NS.Sportsman(this.team, {'groupid': this.group.id}) : this.team.memberList.get(memberid);

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
    NS.MemberGroupRowWidget = MemberGroupRowWidget;	
};