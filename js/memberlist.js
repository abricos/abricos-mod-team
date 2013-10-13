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
	
	var BW = Brick.mod.widget.Widget;
	var buildTemplate = this.buildTemplate;
	
	var MemberGroupListWidget = function(container, teamid, cfg){
		cfg = L.merge({
			'modName': 'team'
		}, cfg || {});

		MemberGroupListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, teamid, cfg);
	};
	YAHOO.extend(MemberGroupListWidget, BW, {
		init: function(teamid, cfg){
			this.teamid = teamid;
			this.cfg = cfg;
			
			this.team = null;
			
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
			switch(el.id){
			case tp['bempadd']: this.showMemberEditor(); return true;
			case tp['bgroupadd']: this.showMemberGroupEditor(); return true;
			}

			var ws = this._wList;
			for (var i=0;i<ws.length;i++){
				if (ws[i].onClick(el)){ return true; }
			}

			return false;
		},
		render: function(){
			var team = this.team;
			if (!L.isValue(team) || !L.isValue(team.memberGroupList)){ return; }
			
			this.elSetVisible('btns', team.role.isAdmin);

			var __self = this;
			
			this._clearWS();

			var ws = this._wList;
			team.memberGroupList.foreach(function(group){
				ws[ws.length] = new NS.MemberGroupRowWidget(__self.gel('list'), team, group, {
					'onReloadList': function(){
						__self.reloadList();
					}
				});
			});

			for (var i=0;i<ws.length;i++){
				ws[i].render();
			}

			this.memberListWidget = new NS.MemberListWidget(this.gel('emplist'), team, {
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
			var __self = this, team = this.team, 
				mcfg = team.manager.cfg['memberGroupEditor'];
			var group = groupid==0 ? new NS.MemberGroup() : team.memberGroupList.get(groupid);
			
			this.componentLoad(mcfg['module'], mcfg['component'], function(){
				__self.elHide('btns,list,emplist');

				__self._editor = new Brick.mod[mcfg['module']][mcfg['widget']](__self.gel('editor'), team, group, function(act){
					__self.closeEditors();
					if (act == 'save'){ __self.render(); }
				});
				
			}, {'hide': 'bbtns', 'show': 'edloading'});
		},
		showMemberEditor: function(memberid){
			memberid = memberid||0;
			this.closeEditors();
			
			var __self = this, team = this.team, mcfg = this.team.manager.cfg['memberEditor'],
				member = memberid==0 ? new team.manager.MemberClass(team) : list.get(memberid);

			this.componentLoad(mcfg['module'], mcfg['component'], function(){
				__self.elHide('btns,list,view');
				
				__self._editor = new Brick.mod[mcfg['module']][mcfg['widget']](__self.gel('editor'), team, member, function(act, newMember){
					__self.closeEditors();
					
					if (act == 'save'){
						__self.render(); 
					}
				});
			}, {'hide': 'bbtns', 'show': 'edloading'});
		}
	});
	NS.MemberGroupListWidget = MemberGroupListWidget;

	var MemberGroupRowWidget = function(container, team, group, cfg){
		cfg = L.merge({
			'onReloadList': null
		}, cfg || {});
		MemberGroupRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'row', 'isRowWidget': true
		}, team, group, cfg);
	};
	YAHOO.extend(MemberGroupRowWidget, BW, {
		init: function(team, group, cfg){
			this.team = team;
			this.group = group;
			this.cfg = cfg;
			this._editor = null;
		},
		buildTData: function(team, group, cfg){
			return {'tl': group.title};
		},
		onClick: function(el, tp){
			var tp = this._TId['row'];
			switch(el.id){
			case tp['bgroupadd']: this.showMemberGroupEditor(); return true;
			case tp['bempadd']: this.showMemberEditor(); return true;
			}
			return false;
		},
		render: function(){
			var team = this.team, group = this.group;
			
			this.elSetVisible('btns', team.role.isAdmin);
			this.elSetHTML('grouptl', group.title);

			this.memberListWidget = new NS.MemberListWidget(this.gel('emplist'), team, {
				'groupid': group.id
			});
		},
		closeEditors: function(){
			if (L.isNull(this._editor)){ return; }
			this._editor.destroy();
			this._editor = null;
			this.elShow('btns,list,emplist');
		},
		showMemberGroupEditor: function(){
			this.closeEditors();
			var __self = this, team = this.team, group = this.group,
				mcfg = team.manager.cfg['memberGroupEditor'];
			this.componentLoad(mcfg['module'], mcfg['component'], function(){
				__self.elHide('btns,list,emplist');

				__self._editor = new Brick.mod[mcfg['module']][mcfg['widget']](__self.gel('editor'), team, group, function(act){
					__self.closeEditors();
					if (act == 'save'){ __self.render(); }
				});
				
			}, {'hide': 'bbtns', 'show': 'edloading'});
		},
		showMemberEditor: function(memberid){
			memberid = memberid||0;
			this.closeEditors();
			
			var __self = this, team = this.team, group = this.group, 
				mcfg = this.team.manager.cfg['memberEditor'],
				member = memberid==0 ? new team.manager.MemberClass(team) : list.get(memberid);

			this.componentLoad(mcfg['module'], mcfg['component'], function(){
				__self.elHide('btns,list,view');
				
				__self._editor = new Brick.mod[mcfg['module']][mcfg['widget']](__self.gel('editor'), team, member, function(act, newMember){
					__self.closeEditors();
					
					if (act == 'save'){
						__self.render(); 
					}
				});
				__self._editor.groupSelectWidget.setValue(group.id);
			}, {'hide': 'bbtns', 'show': 'edloading'});
		}
		
	});
    NS.MemberGroupRowWidget = MemberGroupRowWidget;	
};