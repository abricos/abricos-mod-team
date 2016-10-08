var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['policies.js', 'lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.TeamPoliciesWidget = Y.Base.create('TeamPoliciesWidget', SYS.AppWidget, [
        NS.PluginWidgetExt
    ], {
        onInitAppWidget: function(err, appInstance){
            var team = this.get('team'),
                teamid = team.get('id');

            this.set('waiting', true);
            appInstance.policies(teamid, function(err, result){
                this.set('waiting', false);
                if (err){
                    return;
                }
                this.set('policyList', result.policyList);
                this.set('roleList', result.roleList);
                this._onLoadPolicies()
            }, this);
        },
        _onLoadPolicies: function(){

            var actionList = this.get('appInstance').get('actionList'),
                modules = actionList.toArray('module', {distinct: true}),
                i18n = NS.policies.language;

            NS.uses(modules, 'policies', function(){

                actionList.each(function(action){

                    var name = action.get('group') + '.' + action.get('name'),
                        key = 'policies.action.item.' + name,
                        title = i18n.get(key);

                    action.set('title', title ? title : name);

                    var ownerNS = Brick.mod[action.get('module')];

                    if (!ownerNS){
                        return;
                    }

                    title = ownerNS.policies && ownerNS.policies.language
                        ? ownerNS.policies.language.get(key) : '';

                    if (title){
                        action.set('title', title);
                    }

                }, this);

                this.renderPolicyList();

            }, this);
        },
        _isSetAction: function(policyid, action){
            var mask = 0;
            this.get('roleList').some(function(role){
                if (role.get('policyid') === policyid
                    && action.get('group') === role.get('group')){
                    mask = role.get('mask');
                    return true;
                }
            }, this);
            return mask & action.get('code');
        },
        renderPolicyList: function(){
            var tp = this.template,
                actionList = this.get('appInstance').get('actionList'),
                ownerApp = this.get('ownerApp'),
                lst = "",
                activePolicyId = this.get('activePolicyId');

            this.get('policyList').each(function(policy){
                if (activePolicyId === 0){
                    activePolicyId = policy.get('id');
                    this.set('activePolicyId', activePolicyId)
                }
                lst += tp.replace('policyRow', {
                    activeClass: policy.get('id') === activePolicyId ? 'active' : '',
                    id: policy.get('id'),
                    title: policy.get('title')
                });
            }, this);

            tp.setHTML('policyList', lst);

            var lstAction = "";

            actionList.each(function(action){
                lstAction += tp.replace('actionRow', {
                    id: action.get('id'),
                    title: action.get('title'),
                    checked: this._isSetAction(activePolicyId, action) ? 'checked' : ''
                });
            }, this);

            tp.setHTML('actionList', tp.replace('actionTable', {
                rows: lstAction
            }));
        },
        onClick: function(e){
            var id = e.defineTarget.getData('id') | 0;
            switch (e.dataClick) {
                case 'policyItem':
                    this.set('activePolicyId', id);
                    this.renderPolicyList();
                    return true;
            }
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,policyRow,actionTable,actionRow'},
            ownerApp: {
                readOnly: true,
                getter: function(){
                    var team = this.get('team');
                    return Brick.mod[team.get('module')].appInstance;
                }
            },
            activePolicyId: {value: 0},
            policyList: {},
            roleList: {},
        }
    });
};
