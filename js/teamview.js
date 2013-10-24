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
		L = YAHOO.lang;

	var buildTemplate = this.buildTemplate;
	var isem = function(s){ return L.isString(s) && s.length > 0; };
	
	var TeamViewWidget = function(container, modname, teamid, cfg){
		cfg = L.merge({
			'override': null
		}, cfg || {});
		
		TeamViewWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget', 
			'override': cfg['override']
		}, modname, teamid);
	};
	YAHOO.extend(TeamViewWidget, Brick.mod.widget.Widget, {
		init: function(modname, teamid){
			this.modname = modname;
			this.teamid = teamid;
			this.team = null;

			this._editor = null;
		},
		buildTData: function(modname, teamid){
			return {
				// 'urlmembers': NS.navigator.sportclub.depts.view(teamid)
			};
		},
		onLoad: function(modname, teamid){
			
			var NSMod = Brick.mod[modname];
			if (!L.isValue(NSMod)){ return; }

			var __self = this;
			NSMod.initManager(function(man){
				man.teamLoad(teamid, function(team){
					__self.onLoadTeam(team);
				});
			});
		},
		onLoadTeam: function(team){
			this.team = team;
			
			this.elHide('loading');
			this.render();
		},
		render: function(){
			this.elHide('loading,nullitem,rlwrap');
			this.elHide('fldsite,flddescript,fldemail');

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
				'members': team.memberCount,
				'site': team.siteHTML,
				'descript': team.descript
			});
			
			this.elSetVisible('fldemail', isem(team.email));
			this.elSetVisible('fldsite', isem(team.site));
			this.elSetVisible('flddescript', isem(team.descript));
			this.elSetVisible('fldmembers', team.memberCount > 0);
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bedit']: this.showTeamEditor(); return true;
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
		showTeamEditor: function(){
			this.closeEditors();

			var __self = this, team = this.team, mcfg = team.manager.cfg['teamEditor'];

			this.componentLoad(mcfg['module'], mcfg['component'], function(){
				__self.elHide('btns,view');
				
				__self._editor = new Brick.mod[mcfg['module']][mcfg['widget']](__self.gel('editor'), __self.modname, team.id, function(act){
					__self.closeEditors();
					
					if (act == 'save'){ 
						__self.render();
						Brick.Page.reload();
					}
				});
			}, {'hide': 'bbtns', 'show': 'edloading'});
		},
		showRemovePanel: function(){
			var __self = this, team = this.team, mcfg = team.manager.cfg['teamRemove'];

			this.componentLoad(mcfg['module'], mcfg['component'], function(){
				__self._editor = new Brick.mod[mcfg['module']][mcfg['panel']](team, function(){
					Brick.Page.reload("#app="+team.module+"/wspace/ws");
				});
			}, {'hide': 'bbtns', 'show': 'edloading'});			
		}
	});
	NS.TeamViewWidget = TeamViewWidget;
	
};