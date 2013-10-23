/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: '{C#MODNAME}', files: ['lib.js']}
	]
};
Component.entryPoint = function(NS){

	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		R = NS.roles;

	var buildTemplate = this.buildTemplate;
	var isem = function(s){ return L.isString(s) && s.length > 0; };
	
	var TeamViewWidget = function(container, teamid){
		TeamViewWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, teamid);
	};
	YAHOO.extend(TeamViewWidget, Brick.mod.widget.Widget, {
		init: function(teamid){
			this.teamid = teamid;
			this.team = null;

			this._editor = null;
		},
		buildTData: function(teamid){
			return {
				// 'urlemps': NS.navigator.sportclub.depts.view(teamid)
			};
		},
		onLoad: function(teamid){
			var __self = this;
			NS.initManager(function(){
				NS.manager.teamLoad(teamid, function(team){
					__self._onLoadManager(team);
				});
			});
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bedit']: this.showEditor(); return true;
			case tp['bremove']: this.showRemovePanel(); return true;
			}
			return false;
		},
		closeEditors: function(){
			if (L.isNull(this._editor)){ return; }
			this._editor.destroy();
			this._editor = null;
			this.elShow('btns,view');
		},
		_loadEditor: function(component, callback){
			this.closeEditors();
			this.componentLoad('{C#MODNAME}', component, callback, {
				'hide': 'bbtns', 'show': 'edloading'
			});
		},
		showEditor: function(){
			var __self = this;
			this._loadEditor('cmeditor', function(){
				__self.showEditorMethod();
			});
		},
		showEditorMethod: function(){
			this.elHide('btns,view');
			var __self = this;
			this._editor = new NS.SportclubEditorWidget(this.gel('editor'), this.team.id, function(act){
				__self.closeEditors();
				if (act == 'save'){ 
					__self.render();
					Brick.Page.reload();
				}
			});
		},
		showRemovePanel: function(){
			var team = this.team;
			this._loadEditor('cmeditor', function(){
				new NS.SportclubRemovePanel(team, function(){
					Brick.console('remove');
				});
			});
		},
		_onLoadManager: function(team){
			this.team = team;
			this.render();
		},
		render: function(){
			this.elHide('loading,nullitem,rlwrap');
			this.elHide('fldsite,fldphone,fldaddress,flddescript,fldemail');

			var team = this.team;

			if (L.isNull(team)){
				this.elShow('nullitem');
			}else{
				this.elShow('rlwrap');
			}
			if (L.isNull(team)){ return; }
			
			this.elSetVisible('btns', team.role.isAdmin);

			this.elSetHTML({
				'email': team.email,
				'emps': team.memberCount,
				'site': team.siteHTML,
				'descript': team.descript,
				'phone': team.phone,
				'address': team.address
			});
			
			this.elSetVisible('fldemail', isem(team.email));
			this.elSetVisible('fldsite', isem(team.site));
			this.elSetVisible('fldphone', isem(team.phone));
			this.elSetVisible('fldaddress', isem(team.address));
			this.elSetVisible('flddescript', isem(team.descript));
		}
	});
	NS.TeamViewWidget = TeamViewWidget;
	
};