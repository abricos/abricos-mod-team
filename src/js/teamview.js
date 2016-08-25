var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Dom = YAHOO.util.Dom,
        L = YAHOO.lang;

    var buildTemplate = this.buildTemplate;
    var isem = function(s){
        return L.isString(s) && s.length > 0;
    };

    var TeamViewWidget = function(container, modName, teamid, cfg){
        cfg = L.merge({
            'override': null
        }, cfg || {});

        TeamViewWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'widget',
            'override': cfg['override']
        }, modName, teamid);
    };
    YAHOO.extend(TeamViewWidget, Brick.mod.widget.Widget, {
        init: function(modName, teamid){
            this.modName = modName;
            this.teamid = teamid;
            this.team = null;

            this._editor = null;
        },
        buildTData: function(modName, teamid){
            return {
                // 'urlmembers': NS.navigator.sportclub.depts.view(teamid)
            };
        },
        onLoad: function(modName, teamid){

            var __self = this;
            NS.teamLoad(teamid, function(team){
                __self.onLoadTeam(team);
            });
        },
        onLoadTeam: function(team){
            this.team = team;

            this.elHide('loading');

            if (!L.isValue(team)){
                this.elShow('nullitem');
                return;
            }

            this.elShow('rlwrap');

            if (team.role.isAdmin){
                // подгрузить редакторы
                var __self = this, mcfg = team.manager.cfg['teamEditor'],
                    sh = {'hide': 'bbtns', 'show': 'edloading'};
                this.componentLoad(mcfg['module'], mcfg['component'], function(){
                    mcfg = team.manager.cfg['teamRemove'];
                    __self.componentLoad(mcfg['module'], mcfg['component'], null, sh);
                }, sh);
            }
            this.render();
        },
        render: function(){
            if (!L.isValue(this.team)){
                return;
            }

            this.elHide('fldsite,flddescript,fldemail');

            var team = this.team;

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
            switch (el.id) {
                case tp['bedit']:
                    this.showTeamEditor();
                    return true;
                case tp['bremove']:
                    this.showRemovePanel();
                    return true;
            }
            return false;
        },
        closeEditors: function(){
            if (L.isNull(this._editor)){
                return;
            }
            this._editor.destroy();
            this._editor = null;
            this.elShow('btns,view');
        },
        showTeamEditor: function(){
            this.closeEditors();

            var __self = this, team = this.team, mcfg = team.manager.cfg['teamEditor'];

            this.elHide('btns,view');

            this._editor = new Brick.mod[mcfg['module']][mcfg['widget']](this.gel('editor'), team.id, {
                'modName': __self.modName,
                'callback': function(act){
                    __self.closeEditors();

                    if (act == 'save'){
                        __self.render();
                        Brick.Page.reload();
                    }
                }
            });
        },
        showRemovePanel: function(){
            var team = this.team, mcfg = team.manager.cfg['teamRemove'];
            this._editor = new Brick.mod[mcfg['module']][mcfg['panel']](team, function(){
                var m = team.module;
                if (team.parentModule.length > 0){
                    m = team.parentModule;
                }
                Brick.Page.reload("#app=" + m + "/wspace/ws");
            });
        }
    });
    NS.TeamViewWidget = TeamViewWidget;

};