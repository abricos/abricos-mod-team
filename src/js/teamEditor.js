var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['editor.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.TeamCreateWidget = Y.Base.create('TeamCreateWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                lst = "";

            appInstance.get('pluginList').each(function(plugin){
                if (!plugin.get('isCommunity')){
                    return;
                }
                lst += tp.replace('option', {
                    id: plugin.get('id'),
                    title: plugin.get('title')
                });

                tp.setHTML('select', tp.replace('select', {
                    rows: lst
                }));
            }, this);
        },
        save: function(){
            var tp = this.template,
                ownerModule = tp.getValue('select.id'),
                plugin = this.get('appInstance').get('pluginList').getById(ownerModule);

            if (!plugin || !plugin.get('isCommunity')){
                return;
            }
            this.go('team.edit', 0, ownerModule);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'create,select,option'},
        }
    });

    NS.TeamWrapEditorWidget = Y.Base.create('TeamWrapEditorWidget', SYS.AppWidget, [
        SYS.ContainerWidgetExt,
    ], {
        onInitAppWidget: function(err, appInstance){
            var teamid = this.get('teamid'),
                ownerModule = this.get('ownerModule');

            this.set('waiting', true);
            if (teamid === 0){
                var team = new (appInstance.get('Team'))({
                    appInstance: appInstance,
                    module: ownerModule,
                    visibility: 'public'
                });
                this._onLoadTeam(team);
            } else {
                appInstance.team(teamid, function(err, result){
                    if (err){
                        this.set('waiting', false);
                        return;
                    }
                    this._onLoadTeam(result.team);
                }, this);
            }
        },
        _onLoadTeam: function(team){
            var tp = this.template,
                ownerModule = team.get('module');

            NS.uses([ownerModule], 'teamEditor', function(){
                this.set('waiting', false);

                var EditorWidget = Brick.mod[ownerModule].TeamEditorWidget;
                if (!EditorWidget){
                    return;
                }
                this.addWidget('editor', new EditorWidget({
                    srcNode: tp.one('editor'),
                    team: team
                }));

            }, this);
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'editorWrap'},
            teamid: NS.ATTRIBUTE.teamid,
            ownerModule: {value: ''}
        },
        parseURLParam: function(args){
            return {
                teamid: args[0] | 0,
                ownerModule: args[1] || ''
            };
        }
    });

    var TeamEditorWidgetExt = function(){
    };
    TeamEditorWidgetExt.ATTRS = {
        teamApp: NS.ATTRIBUTE.teamApp,
        team: NS.ATTRIBUTE.team,
    };
    TeamEditorWidgetExt.prototype = {
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                team = this.get('team');

            tp.setValue({
                title: team.get('title')
            });

            this.onLoadTeam(team);
        },
        onLoadTeam: function(team){
        },
        onFillToJSON: function(data){
            return data;
        },
        toJSON: function(){
            var tp = this.template,
                team = this.get('team'),
                data = {
                    teamid: this.get('team').get('id'),
                    module: team.get('module'),
                    title: tp.getValue('title')
                };

            return this.onFillToJSON(data);
        },
        save: function(){
            var teamApp = this.get('teamApp'),
                data = this.toJSON();

            this.set('waiting', true);
            teamApp.teamSave(data, function(err, result){
                this.set('waiting', false);
                this.onSave(err, result);
            }, this);
        },
        onSave: function(err, result){
            if (!err && result && result.teamSave){
                this.go('team.view', result.teamSave.teamid);
            }
        }
    };
    NS.TeamEditorWidgetExt = TeamEditorWidgetExt;

};
