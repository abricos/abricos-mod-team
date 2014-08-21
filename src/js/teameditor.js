/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: '{C#MODNAME}', files: ['editor.js', 'lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		L = YAHOO.lang;

	var buildTemplate = this.buildTemplate;
	
	var TeamCreateWizardWidget = function(container, cfg){
		cfg = L.merge({
			'modName': '{C#MODNAME}',
			'callback': null,
			'override': null
		}, cfg || {});

		TeamCreateWizardWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'wizard',
			'override': cfg['override']
		}, cfg);
	};
	YAHOO.extend(TeamCreateWizardWidget, Brick.mod.widget.Widget, {
		init: function(cfg){
			this.cfg = cfg;
			this.manager = null;
		},
		onLoad: function(cfg){
			var __self = this;
			
			NS.Manager.init(cfg['modName'], function(man){
				__self.onLoadManager(man);
			});
		},
		onLoadManager: function(man){
	
			if (!L.isValue(man)){ return; }

			this.manager = man;
			
			if (man.initData.typeInfoList.count() == 0){
				this.showTeamEditor(man);
				return;
			}
			
			this.typeSelectWidget = new NS.TeamTypeSelectWidget(this.gel('teamtype'), man);
			
			this.elHide('loading');
			this.elShow('wizard');
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bcreate']:
			case tp['bsave']: this.save(); return true;
			case tp['bcancel']: this.cancel(); return true;
			}
			return false;
		},
		cancel: function(){
			NS.life(this.cfg['callback'], 'cancel');
		},
		save: function(){
			var teamType = this.typeSelectWidget.getValue();
			
			if (!L.isString(teamType)){
				teamType = '';
			}
			
			if (teamType.length == ""){
				teamType = this.cfg['modName'];
			}
			
			this.elShow('loading');
			this.elHide('wizard');

			var __self = this;
			NS.Manager.init(teamType, function(man){
				__self.showTeamEditor(man);
			});
		},
		showTeamEditor: function(manager){
			var __self = this, cfg = this.cfg, mcfg = manager.cfg['teamEditor'];

			this.componentLoad(mcfg['module'], mcfg['component'], function(){
				__self.elShow('editor');
				__self.elHide('loading,wizard');

				__self._editor = new Brick.mod[mcfg['module']][mcfg['widget']](__self.gel('editor'), 0, {
					'modName': manager.modName,
					'callback': cfg['callback']
				});
			});			
		}
	});
	NS.TeamCreateWizardWidget = TeamCreateWizardWidget;
	
	var TeamEditorWidget = function(container, teamid, cfg){
		cfg = L.merge({
			'modName': '{C#MODNAME}',
			'callback': null,
			'override': null
		}, cfg || {});

		TeamEditorWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget',
			'override': cfg['override']
		}, teamid|0, cfg);
	};
	YAHOO.extend(TeamEditorWidget, Brick.mod.widget.Widget, {
		init: function(teamid, cfg){
			this.teamid = teamid;
			this.cfg = cfg;

			this.team = null;
			this.manager = null;
		},
		buildTData: function(teamid, cfg){
			return {'cledst': teamid==0?'edstnew': 'edstedit'};
		},
		onLoad: function(teamid, cfg){
			var __self = this;
			
			NS.Manager.init(cfg['modName'], function(man){
				if (!L.isValue(man)){ return; }

				__self.manager = man;
				if (teamid == 0){
					__self.onLoadTeam(new man.TeamClass({
						'dtl': {}
					}));
				}else{
					man.teamLoad(teamid, function(team){
						__self.onLoadTeam(team);
					});
				}
			});
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bcreate']:
			case tp['bsave']: this.save(); return true;
			case tp['bcancel']: this.cancel(); return true;
			}
			return false;
		},
		onLoadTeam: function(team){
			this.team = team;
			this.logoWidget = new NS.LogoWidget(this.gel('logo'), team.logo);
	
			this.render();
		},
		render: function(){
			var team = this.team;
			if (L.isNull(team)){ return; }
			this.elHide('loading');
			this.elShow('editor');
			
			this.elSetValue({
				'title': team.title,
				'email': team.email,
				'descript': team.descript,
				'site': team.site
			});
		},
		cancel: function(){
			NS.life(this.cfg['callback'], 'cancel');
		},
		getSaveData: function(){
			return {
				'id': this.team.id,
				'uid': this.team.userid,
				'tl': this.gel('title').value,
				'eml': this.gel('email').value,
				'dsc': this.gel('descript').value,
				'site': this.gel('site').value,
				'logo': this.logoWidget.getValue()
			};
		},
		save: function(){
			var __self = this;
			var sd = this.getSaveData();
			
			this.elHide('btns');
			this.elShow('bloading');
			
			this.manager.teamSave(sd, function(team){
				__self.elShow('btns');
				__self.elHide('bloading');
				NS.life(__self.cfg['callback'], 'save', team);
			});
		}		
	});
	NS.TeamEditorWidget = TeamEditorWidget;
	
	
	var TeamRemovePanel = function(team, callback){
		this.team = team;
		this.callback = callback;
		TeamRemovePanel.superclass.constructor.call(this, {fixedcenter: true});
	};
	YAHOO.extend(TeamRemovePanel, Brick.widget.Dialog, {
		initTemplate: function(){
			return buildTemplate(this, 'removepanel').replace('removepanel');
		},
		onClick: function(el){
			var tp = this._TId['removepanel'];
			switch(el.id){
			case tp['bcancel']: this.close(); return true;
			case tp['bremove']: this.remove(); return true;
			}
			return false;
		},
		remove: function(){
			var TM = this._TM, gel = function(n){ return  TM.getEl('removepanel.'+n); },
				__self = this;
			Dom.setStyle(gel('btns'), 'display', 'none');
			Dom.setStyle(gel('bloading'), 'display', '');
			this.team.manager.teamRemove(this.team, function(){
				__self.close();
				NS.life(__self.callback);
			});
		}
	});
	NS.TeamRemovePanel = TeamRemovePanel;

};